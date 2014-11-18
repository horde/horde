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
 * Composite storage driver for the IMP_Maillog class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Storage_Composite extends IMP_Maillog_Storage_Base
{
    /**
     * List of drivers.
     *
     * @var array
     */
    protected $_drivers;

    /**
     * Constructor.
     *
     * @param array $drivers  List of drivers.
     */
    public function __construct(array $drivers)
    {
        $this->_drivers = $drivers;
    }

    /**
     */
    public function saveLog(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    )
    {
        foreach ($this->_drivers as $val) {
            if ($val->saveLog($msg, $log)) {
                return true;
            }
        }

        return false;
    }

    /**
     */
    public function getLog(IMP_Maillog_Message $msg, array $types = array())
    {
        $out = array();

        foreach ($this->_drivers as $val) {
            $out = array_merge($out, $val->getLog($msg, $types));
        }

        return $out;
    }

    /**
     */
    public function deleteLogs(array $msgs)
    {
        foreach ($this->_drivers as $val) {
            $val->deleteLogs($msgs);
        }
    }

    /**
     */
    public function getChanges($ts)
    {
        $out = array();

        foreach ($this->_drivers as $val) {
            $out = array_merge($out, $val->getChanges($ts));
        }

        return $out;
    }

}
