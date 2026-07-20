<?php
/**
 * Console command for probing EntryQuery authorization SQL.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\console\controllers;

use amici\SuperContentAccess\Plugin;
use Craft;
use craft\elements\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Spike console command: prove EntryQuery interception and SQL constraint injection.
 *
 * Examples:
 *   php craft super-content-access/query-probe
 *   php craft super-content-access/query-probe --seed=1 --userId=1
 *   php craft super-content-access/query-probe --mode=baseline
 *   php craft super-content-access/query-probe --mode=constrained --userId=1
 *   php craft super-content-access/query-probe --clear
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class QueryProbeController extends Controller
{
    /**
     * Probe mode: baseline, constrained, or both.
     *
     * @var string
     */
    public string $mode = 'both';

    /**
     * Optional section handle filter.
     *
     * @var string|null
     */
    public ?string $section = null;

    /**
     * User ID for the synthetic authorization context.
     *
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * Entry ID to seed a sample policy on.
     *
     * @var int|null
     */
    public ?int $entryId = null;

    /**
     * Maximum number of entries to return in probe output.
     *
     * @var int
     */
    public int $limit = 20;

    /**
     * Whether to seed a sample policy before probing.
     *
     * @var bool
     */
    public bool $seed = false;

    /**
     * Whether to clear all policies before probing.
     *
     * @var bool
     */
    public bool $clear = false;

    /**
     * Whether to run the probe as a guest.
     *
     * @var bool
     */
    public bool $guest = false;

    /**
     * Returns console options supported by this controller.
     *
     * @param string $actionID Action ID being run.
     *
     * @return array Supported option names.
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'mode',
            'section',
            'userId',
            'entryId',
            'limit',
            'seed',
            'clear',
            'guest',
        ]);
    }

    /**
     * Returns short aliases for console options.
     *
     * @return array<string, string> Option aliases.
     */
    public function optionAliases(): array
    {
        return [
            'm' => 'mode',
            's' => 'section',
            'u' => 'userId',
            'e' => 'entryId',
            'l' => 'limit',
        ];
    }

    /**
     * Runs baseline and/or constrained EntryQuery probes and prints SQL + results.
     *
     * @return int Console exit code.
     */
    public function actionIndex(): int
    {
        $plugin = Plugin::getInstance();
        $probe = $plugin->getQueryProbe();
        $integrator = $plugin->getElementQueryIntegrator();

        // Disable production integrator so baseline/constrained modes stay comparable.
        $integrator->disable();

        try {
            return $this->runProbeAction($probe);
        } finally {
            $integrator->enable();
        }
    }

    /**
     * Executes the probe workflow after optional seed/clear steps.
     *
     * @param \amici\SuperContentAccess\query\QueryProbe $probe Query probe service.
     *
     * @return int Console exit code.
     */
    private function runProbeAction($probe): int
    {
        if ($this->clear) {
            $removed = $probe->clearAllPolicies();
            $this->stdout("Cleared {$removed} access policy row(s).\n", Console::FG_YELLOW);
            if (!$this->seed && $this->mode === 'both') {
                return ExitCode::OK;
            }
        }

        if ($this->seed) {
            $seedUserId = $this->userId ?? 1;
            $seed = $probe->seedSamplePolicy($this->entryId, $seedUserId);
            $state = $seed['created'] ? 'created' : 'reused';
            $this->stdout(
                "Seed {$state}: entryId={$seed['entryId']} policyId={$seed['policyId']} "
                . "principal=user:{$seedUserId} (id {$seed['principalId']})\n",
                Console::FG_CYAN
            );
            $this->stdout(
                "Protected entry is only visible in constrained mode when --userId matches "
                . "(or type=public/guest principals are added).\n\n"
            );
        }

        $userId = $this->userId;
        $groupIds = [];
        $isGuest = $this->guest;

        if ($userId !== null && !$isGuest) {
            $user = User::find()->id($userId)->status(null)->one();
            if ($user) {
                $groupIds = array_map(
                    static fn($group): int => (int)$group->id,
                    $user->getGroups()
                );
            } else {
                $this->stdout("Warning: userId {$userId} not found; using raw id only.\n", Console::FG_YELLOW);
            }
        }

        if ($isGuest) {
            $userId = null;
            $groupIds = [];
        }

        $modes = match ($this->mode) {
            'baseline' => ['baseline'],
            'constrained' => ['constrained'],
            'both' => ['baseline', 'constrained'],
            default => null,
        };

        if ($modes === null) {
            $this->stderr("Invalid --mode. Use baseline, constrained, or both.\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $this->stdout("Context: ", Console::FG_GREY);
        $this->stdout(
            $isGuest
                ? "guest\n"
                : 'userId=' . ($userId ?? 'null') . ' groupIds=[' . implode(',', $groupIds) . "]\n"
        );
        $this->stdout(
            'Filters: section=' . ($this->section ?? '(all)') . " limit={$this->limit}\n\n"
        );

        foreach ($modes as $mode) {
            $this->runProbe($mode, $userId, $groupIds, $isGuest);
        }

        return ExitCode::OK;
    }

    /**
     * Runs one probe mode and prints SQL plus matching entries.
     *
     * @param string $mode Probe mode name.
     * @param int|null $userId User ID for the synthetic context.
     * @param int[] $groupIds Group IDs for the synthetic context.
     * @param bool $isGuest Whether the synthetic context is a guest.
     *
     * @return void Nothing is returned.
     */
    private function runProbe(string $mode, ?int $userId, array $groupIds, bool $isGuest): void
    {
        $probe = Plugin::getInstance()->getQueryProbe();
        $detach = null;

        if ($mode === 'constrained') {
            $detach = $probe->attachConstraintListener($userId, $groupIds, $isGuest);
        }

        try {
            $query = $probe->createEntryQuery($this->section, $this->limit);
            $sql = $query->getRawSql();
            $entries = $query->all();
            $count = count($entries);
        } finally {
            if ($detach !== null) {
                $detach();
            }
        }

        $label = strtoupper($mode);
        $this->stdout("=== {$label} ===\n", Console::FG_GREEN);
        $this->stdout("Count: {$count}\n", Console::FG_YELLOW);
        $this->stdout("SQL:\n{$sql}\n\n");

        if ($entries === []) {
            $this->stdout("(no entries)\n\n");
            return;
        }

        $this->stdout("Entries:\n");
        foreach ($entries as $entry) {
            $this->stdout(sprintf(
                "  #%d  %s  [%s]\n",
                $entry->id,
                $entry->title ?: '(untitled)',
                $entry->getSection()?->handle ?? '?'
            ));
        }
        $this->stdout("\n");
    }
}
