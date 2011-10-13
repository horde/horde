<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * Raised if an invalid option is seen on the command line.
 *
 * @category Horde
 * @package  Argv
 */
class Horde_Argv_BadOptionException extends Horde_Argv_OptionException
{
    public function __construct($opt_str)
    {
        parent::__construct(sprintf('no such option: %s', $opt_str));
    }

}
