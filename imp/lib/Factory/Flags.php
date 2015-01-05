<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Flags object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Flags extends Horde_Core_Factory_Injector implements Horde_Shutdown_Task
{
    /**
     * @var IMP_Flags
     */
    private $_instance = null;

    /**
     * Return the IMP_Flags instance.
     *
     * @return IMP_Flags  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        try {
            $this->_instance = $GLOBALS['session']->get('imp', 'flags');
        } catch (Exception $e) {
            Horde::log('Could not unserialize stored IMP_Flags object.', 'DEBUG');
        }

        if (is_null($this->_instance)) {
            $this->_instance = new IMP_Flags();
        }

        Horde_Shutdown::add($this);

        return $this->_instance;
    }

    /**
     * Store serialized version of object in the current session.
     */
    public function shutdown()
    {
        /* Only need to store the object if the object has changed. */
        if ($this->_instance->changed) {
            $GLOBALS['session']->set('imp', 'flags', $this->_instance);
        }
    }

}
