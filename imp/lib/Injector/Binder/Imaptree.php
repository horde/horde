<?php
/**
 * Binder for IMP_Imap_Tree::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Imaptree implements Horde_Injector_Binder
{
    /**
     * If an IMP_Imap_Tree object is currently stored in the cache, re-create
     * that object.  Else, create a new instance.
     */
    public function create(Horde_Injector $injector)
    {
        $cache = $injector->getInstance('Horde_Cache');

        if (empty($_SESSION['imp']['cache']['tree'])) {
            $_SESSION['imp']['cache']['tree'] = uniqid(mt_rand() . Horde_Auth::getAuth());
        } elseif ($instance = @unserialize($cache->get($_SESSION['imp']['cache']['tree'], 86400))) {
            return $instance;
        }

        $instance = new IMP_Imap_Tree();
        $instance->cacheId = $_SESSION['imp']['cache']['tree'];

        return $instance;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
