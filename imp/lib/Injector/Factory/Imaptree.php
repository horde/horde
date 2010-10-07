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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class IMP_Injector_Factory_Imaptree
{
    /**
     * Return the IMP_Imap_Tree object.
     *
     * @return IMP_Imap_Tree  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        $instance = null;

        /* If an IMP_Imap_Tree object is currently stored in the cache,
         * re-create that object.  Else, create a new instance. */
        if (empty($_SESSION['imp']['cache']['tree'])) {
            $_SESSION['imp']['cache']['tree'] = strval(new Horde_Support_Randomid());
        } else {
            /* Since IMAP tree generation is so expensive/time-consuming,
             * fallback to storing in the session even if no permanent cache
             * backend is setup. */
            $cache = $injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $cache = $injector->getInstance('Horde_Cache_Session');
            }
            try {
                $instance = @unserialize($cache->get($_SESSION['imp']['cache']['tree'], 86400));
            } catch (Exception $e) {
                Horde::logMessage('Could not unserialize stored IMP_Imap_Tree object.', 'DEBUG');
            }
        }

        if (!($instance instanceof IMP_Imap_Tree)) {
            $instance = new IMP_Imap_Tree();
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
        /* Only need to store the object if the tree has changed. */
        if ($instance->changed) {
            $cache = $injector->getInstance('Horde_Cache');
            if ($cache instanceof Horde_Cache_Null) {
                $cache = $injector->getInstance('Horde_Cache_Session');
            }
            $cache->set($_SESSION['imp']['cache']['tree'], serialize($instance), 86400);
        }
    }

}
