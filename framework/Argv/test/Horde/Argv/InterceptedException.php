<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_InterceptedException extends Exception
{
    public function __construct($error_message = null, $exit_status = null, $exit_message = null)
    {
        $this->error_message = $error_message;
        $this->exit_status = $exit_status;
        $this->exit_message = $exit_message;
    }

    public function __toString()
    {
        if ($this->error_message)
            return $this->error_message;
        if ($this->exit_message)
            return $this->exit_message;
        return "intercepted error";
    }

}
