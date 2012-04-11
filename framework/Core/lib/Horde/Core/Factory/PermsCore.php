<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_PermsCore extends Horde_Core_Factory_Injector
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @return Horde_Perms_Core  The newly created concrete instance.
     * @throws Horde_Perms_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return new Horde_Core_Perms(
            $injector->getInstance('Horde_Registry'),
            $injector->getInstance('Horde_Perms')
        );
    }
}
