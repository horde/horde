<?php
/**
 * Jonah_Driver binder.
 * 
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_Injector_Binder_Driver Implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['news']['storage']['driver']);
        $params = Horde::getDriverConfig(array('news', 'storage'), $driver);

        $factory = new Jonah_Injector_Factory_Driver($injector);
        return $factory->getDriver($driver, $params);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
