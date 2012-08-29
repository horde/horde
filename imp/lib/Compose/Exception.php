<?php
/**
 * The IMP_Compose_Exception:: class handles exceptions thrown from the
 * IMP_Compose class.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Compose_Exception extends IMP_Exception
{
    /**
     * Stores information on whether an encryption dialog window needs
     * to be opened.
     *
     * @var string
     */
    public $encrypt = null;

    /**
     * If set, indicates that this identity matches the given to address.
     *
     * @var integer
     */
    public $tied_identity = null;

    /**
     * If true, exception was already logged.
     *
     * @var boolean
     */
    protected $_logged = false;

    /**
     * Creates a new Exception object and immediately logs the message.
     *
     * @param string $log     The log level to immediately log the message to.
     *                        If empty, will only log message if log() is
     *                        explicitly called.
     * @param mixed $message  The exception message, PEAR_Error object, or
     *                        Exception object.
     * @param integer $code   A numeric error code.
     *
     * @return IMP_Compose_Exception  Exception argument.
     */
    static public function createAndLog()
    {
        $e = new self(func_get_arg(1), func_num_args() == 3 ? func_get_arg(2) : null);
        $e->log(func_get_arg(0));
        return $e;
    }

    /**
     * Log error message.
     *
     * @param string $level  Level to log at.
     *
     * @return boolean  True if message was logged.
     */
    public function log($level = 'ERR')
    {
        if ($this->_logged) {
            return false;
        }

        Horde::log($this, $level);
        $this->_logged = true;

        return true;
    }

}
