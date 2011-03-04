<?php
/**
 * A Horde_Injector based factory for the IMP_Imap_Tree object.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Imap_Tree object.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Factory_Imaptree extends Horde_Core_Factory_Injector
{
    /**
     * Indicates that the tree object is being initialized.
     *
     * @var boolean
     */
    private $_isInit = false;

    /**
     * Return the IMP_Imap_Tree object.
     *
     * @return IMP_Imap_Tree  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $session;

        $instance = null;

        /* If an IMP_Imap_Tree object is currently stored in the cache,
         * re-create that object.  Else, create a new instance. */
        if ($session->exists('imp', 'treeob')) {
            /* Since IMAP tree generation is so expensive/time-consuming,
             * fallback to storing in the session even if no permanent cache
             * backend is setup. */
            $cache = $injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $instance = $session->retrieve('imp_imaptree');
            } else {
                try {
                    $instance = @unserialize($cache->get($session->get('imp', 'treeob'), 86400));
                } catch (Exception $e) {
                    Horde::logMessage('Could not unserialize stored IMP_Imap_Tree object.', 'DEBUG');
                }
            }
        } else {
            $session->set('imp', 'treeob', strval(new Horde_Support_Randomid()));
        }

        if (!($instance instanceof IMP_Imap_Tree)) {
            $this->_isInit = true;
            $instance = new IMP_Imap_Tree();
            $this->_isInit = false;
        }

        register_shutdown_function(array($this, 'shutdown'), $instance, $injector);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     *
     * @param IMP_Imap_Tree $instance   Tree object.
     * @param Horde_Injector $injector  Injector object.
     */
    public function shutdown($instance, $injector)
    {
        global $session;

        /* Only need to store the object if the tree has changed. */
        if ($instance->changed) {
            $cache = $injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $session->store($instance, true, 'imp_imaptree');
            } else {
                $cache->set($GLOBALS['session']->get('imp', 'treeob'), serialize($instance), 86400);
            }
        }
    }

    /**
     * Static method: determines if IMP_Imap_Tree has already been initialized
     * in this session.
     *
     * @return boolean  True if the tree object has been initialized.
     */
    static public function initialized()
    {
        return (!$this->_isInit &&
                $GLOBALS['session']->exists('imp', 'treeob'));
    }

}
