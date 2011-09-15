<?php
/**
 * This is the base Driver class for the Sesha application.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
abstract class Sesha_Driver
{
    protected $_params;

    /**
     * Variable holding the items in the inventory.
     *
     * @var array
     */
    protected $_stock;

    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    public function factory($driver = null, $params = null)
    {
        if (is_null($driver))
            $driver = $GLOBALS['conf']['storage']['driver'];

        $driver = basename($driver);

        if (is_null($params))
            $params = Horde::getDriverConfig('storage', $driver);

        $class = 'Sesha_Driver_' . $driver;
        if (class_exists($class)) {
            $sesha = new $class($params);
        } else {
            $sesha = false;
        }

    }
}
