<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * Format help with indented section bodies.
 *
 * @category Horde
 * @package  Argv
 */
class Horde_Argv_IndentedHelpFormatter extends Horde_Argv_HelpFormatter
{
    public function __construct(
        $indent_increment = 2,
        $max_help_position = 24,
        $width = null,
        $short_first = true)
    {
        parent::__construct($indent_increment, $max_help_position, $width, $short_first);
    }

    public function formatUsage($usage)
    {
        return sprintf(Horde_Argv_Translation::t("Usage:") . " %s\n", $usage);
    }

    public function formatHeading($heading)
    {
        return sprintf('%' . $this->current_indent . "s%s:\n", '', $heading);
    }

}
