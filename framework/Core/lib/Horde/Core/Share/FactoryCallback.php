<?php
/**
 * Small factory class for wrapping the injection of Horde_Share objects into
 * the Horde_Share_Object objects. We must use $GLOBALS here instead of keeping
 * a reference to the injector since this object will be serialized along with
 * the Horde_Share_Object object.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Core_Share_FactoryCallback
{
    protected $_app;
    protected $_driver;

    public function __construct($app, $driver)
    {
        $this->_app = $app;
        $this->_driver = $driver;
    }

    public function create()
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_ShareBase')->create($this->_app, $this->_driver);
    }

}
