<?php
/**
 * Helpers for section types that support General Access defaults.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\helpers;

use craft\models\Section;

/**
 * Identifies Craft sections that appear under General Access.
 *
 * Channels and structures are supported. Singles are omitted — set access on
 * the single entry itself.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class SectionAccessHelper
{
    /**
     * Section types that may receive a General Access default.
     *
     * @return string[] Craft section type constants.
     */
    public static function generalAccessTypes(): array
    {
        return [
            Section::TYPE_CHANNEL,
            Section::TYPE_STRUCTURE,
        ];
    }

    /**
     * Whether the section may be configured under General Access.
     *
     * @param Section $section Section to evaluate.
     *
     * @return bool True for channels and structures.
     */
    public static function supportsGeneralAccess(Section $section): bool
    {
        return in_array($section->type, self::generalAccessTypes(), true);
    }
}
