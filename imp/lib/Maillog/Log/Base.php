<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Abstract log entry.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $action  Action.
 * @property-read string $date  Formatted date string.
 * @property-read string $message  Log message.
 * @property integer $timestamp  Timestamp.
 */
abstract class IMP_Maillog_Log_Base
{
    /**
     * Action.
     *
     * @var string
     */
    protected $_action;

    /**
     * Timestamp.
     *
     * @var integer
     */
    protected $_timestamp;

    /**
     */
    public function __get($name)
    {
        global $prefs;

        switch ($name) {
        case 'action':
            return $this->_action;

        case 'date':
            return strftime(
                $prefs->getValue('date_format') . ' ' . $prefs->getValue('time_format'),
                $this->timestamp
            );

        case 'message':
            return $this->_getMessage();

        case 'timestamp':
            if (!$this->_timestamp) {
                $this->_timestamp = time();
            }
            return $this->_timestamp;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'timestamp':
            $this->_timestamp = intval($value);
            break;
        }
    }

    /**
     */
    public function __tostring()
    {
        return $this->message;
    }

    /**
     * The log message.
     *
     * @return string  Log message.
     */
    abstract protected function _getMessage();

}
