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
class IMP_Injector_Factory_Search
{
    /**
     * Return the IMP_Search instance.
     *
     * @return IMP_Search  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        $instance = null;

        if (!empty($_SESSION['imp']['search'])) {
            try {
                $instance = @unserialize($_SESSION['imp']['search']);
            } catch (Exception $e) {
                Horde::logMessage('Could not unserialize stored IMP_Search object.', 'DEBUG');
            }
        }

        if (is_null($instance)) {
            $instance = new IMP_Search();
        }

        register_shutdown_function(array($this, 'shutdown'), $instance, $injector);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     *
     * @param IMP_Search $instance      Tree object.
     * @param Horde_Injector $injector  Injector object.
     */
    public function shutdown($instance, $injector)
    {
        /* Only need to store the object if the object has changed. */
        if ($instance->changed) {
            $_SESSION['imp']['search'] = serialize($instance);
        }
    }

}
