<?php
/**
 * A Horde_Injector:: based Horde_Core_Share_Driver:: factory.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * A Horde_Injector:: based Horde_Share factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Core_Factory_Share extends Horde_Core_Factory_Base
{
    /**
     * Returns the Horde_Share_Base instance.
     *
     * @param string $app     The application scope to use, if not the current
     *                        app.
     * @param string $driver  The share driver. Either empty (use default
     *                        driver from $conf) or a driver name.
     *
     * @return Horde_Core_Share_Driver  The Horde_Share instance.
     * @throws Horde_Exception
     */
    public function create($app = null, $driver = null)
    {
        return new Horde_Core_Share_Driver($this->_injector->getInstance('Horde_Core_Factory_ShareBase')->create($app, $driver));
    }

}
