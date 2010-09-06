<?php
/**
 * Binder for IMP_Search::.
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
class IMP_Injector_Binder_Search implements Horde_Injector_Binder
{
    /**
     * Injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * If an IMP_Search object is currently stored in the session, re-create
     * that object. Else, create a new instance.
     *
     * @param Horde_Injecton $injector  Parent injector.
     */
    public function create(Horde_Injector $injector)
    {
        $this->_injector = $injector;

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

        register_shutdown_function(array($this, 'shutdown'), $instance);

        return $instance;
    }

    /**
     * Store serialized version of object in the current session.
     */
    public function shutdown($instance)
    {
        /* Only need to store the object if the object has changed. */
        if ($instance->changed) {
            $_SESSION['imp']['search'] = serialize($instance);
        }
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
