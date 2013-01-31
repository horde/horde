<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A factory for creating a VFS object for use with compose attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_ComposeVfs extends Horde_Core_Factory_Injector
{
    /**
     * @return Horde_Vfs_Base
     */
    public function create(Horde_Injector $injector)
    {
        return $GLOBALS['conf']['compose']['use_vfs']
            ? $injector->getInstance('Horde_Core_Factory_Vfs')->create()
            : new Horde_Vfs_File(array('vfsroot' => Horde::getTempDir()));
    }

}
