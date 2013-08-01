<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Imap_Tree object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Imaptree extends Horde_Core_Factory_Injector implements Horde_Shutdown_Task
{
    /**
     * @var IMP_Imap_Tree
     */
    private $_instance;

    /**
     * Return the IMP_Imap_Tree object.
     *
     * @return IMP_Imap_Tree  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $registry, $session;

        /* If an IMP_Imap_Tree object is currently stored in the cache,
         * re-create that object.  Else, create a new instance. */
        if ($session->exists('imp', 'treeob')) {
            /* Since IMAP tree generation is so expensive/time-consuming,
             * fallback to storing in the session even if no permanent cache
             * backend is setup. */
            $cache = $injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $this->_instance = $session->retrieve('imp_imaptree');
            } else {
                try {
                    $this->_instance = @unserialize($cache->get($session->get('imp', 'treeob'), 0));
                } catch (Exception $e) {
                    Horde::log('Could not unserialize stored IMP_Imap_Tree object.', 'DEBUG');
                }
            }
        } else {
            $session->set('imp', 'treeob', strval(new Horde_Support_Randomid()));
        }

        if (!($this->_instance instanceof IMP_Imap_Tree)) {
            $this->_instance = new IMP_Imap_Tree();
        }

        switch ($registry->getView()) {
        case $registry::VIEW_DYNAMIC:
        case $registry::VIEW_SMARTMOBILE:
            $this->_instance->track = true;
            break;
        }

        Horde_Shutdown::add($this);

        return $this->_instance;
    }

    /**
     * Store serialized version of object in the current session.
     */
    public function shutdown()
    {
        global $session;

        /* Only need to store the object if the tree has changed. */
        if ($this->_instance->changed) {
            $cache = $this->_injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $session->store($this->_instance, true, 'imp_imaptree');
            } else {
                $cache->set($session->get('imp', 'treeob'), serialize($this->_instance), 86400);
            }
        }
    }

}
