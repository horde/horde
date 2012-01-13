<?php
/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_Factory_VfsDefault extends Horde_Core_Factory_Injector
{
    /**
     * Returns the VFS object for the currently active backend.
     *
     * @return Horde_Vfs  The VFS object.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return $injector
            ->getInstance('Gollem_Factory_Vfs')
            ->create($GLOBALS['session']->get('gollem', 'backend_key'));
    }
}
