<?php
/**
 * The IMP_Compose_Exception:: class handles exceptions thrown from the
 * IMP_Compose class.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
     * @param string $log  The log level to immediately log the message to.
     *                     If empty, will only log message if log() is
     *                     explicitly called.
     */
    public function __construct($message = null, $log = null)
    {
        parent::__construct($message);

        if (!is_null($log)) {
            Horde::logMessage($this, $log);
            $this->_logged = true;
        }
    }

    /**
     * Log error message.
     *
     * @return boolean  True if message was logged.
     */
    public function log()
    {
        if ($this->log) {
            if (!$this->_logged) {
                Horde::logMessage($this, 'ERR');
                $this->_logged = true;
            }
            return true;
        }

        return false;
    }

}
