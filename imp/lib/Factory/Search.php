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
 * A Horde_Injector based factory for the IMP_Search object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Search extends Horde_Core_Factory_Injector implements Horde_Queue_Task
{
    /**
     * @var IMP_Search
     */
    private $_instance;

    /**
     * Return the IMP_Search instance.
     *
     * @return IMP_Search  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        try {
            $this->_instance = $GLOBALS['session']->get('imp', 'search');
        } catch (Exception $e) {
            Horde::log('Could not unserialize stored IMP_Search object.', 'DEBUG');
        }

        if (!$this->_instance) {
            $this->_instance = new IMP_Search();
        }

        $injector->getInstance('Horde_Queue_Storage')->add($this);

        return $this->_instance;
    }

    /**
     * Store serialized version of object in the current session.
     */
    public function run()
    {
        /* Only need to store the object if the object has changed. */
        if ($this->_instance->changed) {
            $GLOBALS['session']->set('imp', 'search', $this->_instance);
        }
    }

}
