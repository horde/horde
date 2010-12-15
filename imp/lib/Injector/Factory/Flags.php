<?php
/**
 * A Horde_Injector based factory for the IMP_Flags object.
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
 * A Horde_Injector based factory for the IMP_Flags object.
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
class IMP_Injector_Factory_Flags
{
    /**
     * Return the IMP_Flags instance.
     *
     * @return IMP_Flags  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        try {
            $instance = $GLOBALS['session']->get('imp', 'flags');
        } catch (Exception $e) {
            Horde::logMessage('Could not unserialize stored IMP_Flags object.', 'DEBUG');
            $instance = null;
        }

        if (is_null($instance)) {
            $instance = new IMP_Flags();
        }

        register_shutdown_function(array($this, 'shutdown'), $instance);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     *
     * @param IMP_Flags $instance  Flags object.
     */
    public function shutdown($instance)
    {
        /* Only need to store the object if the object has changed. */
        if ($instance->changed) {
            $GLOBALS['session']->set('imp', 'flags', $instance);
        }
    }

}
