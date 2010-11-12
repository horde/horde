<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FactoryCallback
 *
 * @author mrubinsk
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
