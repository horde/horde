<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * Raised if an Option instance is created with invalid or
 * inconsistent arguments.
 *
 * @category Horde
 * @package  Argv
 */
class Horde_Argv_OptionException extends Horde_Argv_Exception
{
    public function __construct($msg, $option = null)
    {
        $this->optionId = (string)$option;
        if ($this->optionId) {
            parent::__construct(sprintf('option %s: %s', $this->optionId, $msg));
        } else {
            parent::__construct($msg);
        }
    }

}
