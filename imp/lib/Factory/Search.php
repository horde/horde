<?php
/**
 * A Horde_Injector based factory for the IMP_Search object.
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
 * A Horde_Injector based factory for the IMP_Search object.
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
class IMP_Factory_Search extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Search instance.
     *
     * @return IMP_Search  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        try {
            $instance = $GLOBALS['session']->get('imp', 'search');
        } catch (Exception $e) {
            Horde::logMessage('Could not unserialize stored IMP_Search object.', 'DEBUG');
            $instance = null;
        }

        if (is_null($instance)) {
            $instance = new IMP_Search();
        }

        register_shutdown_function(array($this, 'shutdown'), $instance);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     *
     * @param IMP_Search $instance  Search object.
     */
    public function shutdown($instance)
    {
        /* Only need to store the object if the object has changed. */
        if ($instance->changed) {
            $GLOBALS['session']->set('imp', 'search', $instance);
        }
    }

}
