<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * Raised if an ambiguous option is seen on the command line.
 *
 * @category Horde
 * @package  Argv
 */
class Horde_Argv_AmbiguousOptionException extends Horde_Argv_BadOptionException
{
    public function __construct($opt_str, $possibilities)
    {
        // Have to skip the BadOptionException constructor or the string gets double-prefixed.
        Horde_Argv_OptionException::__construct(sprintf('ambiguous option: %s (%s?)', $opt_str, implode(', ', $possibilities)));
    }

}
