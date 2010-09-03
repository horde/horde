<?php
/**
 * Binder for IMP_Imap_Tree::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Injector_Binder_Imaptree implements Horde_Injector_Binder
{
    /**
     * Injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * If an IMP_Imap_Tree object is currently stored in the cache, re-create
     * that object.  Else, create a new instance.
     */
    public function create(Horde_Injector $injector)
    {
        $this->_injector = $injector;

        if (empty($_SESSION['imp']['cache']['tree'])) {
            $_SESSION['imp']['cache']['tree'] = strval(new Horde_Support_Randomid());
            $instance = null;
        } else {
            /* Since IMAP tree generation is so expensive/time-consuming,
             * fallback to storing in the session even if no permanent cache
             * backend is setup. */
            $cache = $injector->getInstance('Horde_Cache_Factory')->getCache(array('session' => true));
            $instance = @unserialize($cache->get($_SESSION['imp']['cache']['tree'], 86400));
        }

        if (empty($instance)) {
            $instance = new IMP_Imap_Tree();
        }

        register_shutdown_function(array($this, 'shutdown'), $instance);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     */
    public function shutdown($instance)
    {
        /* Only need to store the object if the tree has changed. */
        if ($instance->changed) {
            $cache = $this->_injector->getInstance('Horde_Cache_Factory')->getCache(array('session' => true));
            $cache->set($_SESSION['imp']['cache']['tree'], serialize($instance), 86400);
        }
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
