<?php
/**
 * Binder for IMP_Folder::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Folder implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        $cacheid = empty($GLOBALS['conf']['server']['cache_folders'])
            ? null
            : 'imp_folder_cache|' . Horde_Auth::getAuth() . '|' . $_SESSION['imp']['server_key'];

        return new IMP_Folder($cacheid);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
