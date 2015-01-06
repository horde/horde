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
 * A Horde_Injector based factory for the IMP_Ftree object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Ftree
extends Horde_Core_Factory_Injector
implements Horde_Shutdown_Task
{
    /* Storage key in session. */
    const STORAGE_KEY = 'ftree';

    /**
     * @var IMP_Ftree
     */
    private $_instance;

    /**
     * Return the IMP_Ftree object.
     *
     * @return IMP_Ftree  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $registry, $session;

        $this->_instance = $session->get('imp', self::STORAGE_KEY);

        if (!($this->_instance instanceof IMP_Ftree)) {
            $this->_instance = new IMP_Ftree();
        }

        switch ($registry->getView()) {
        case $registry::VIEW_DYNAMIC:
        case $registry::VIEW_SMARTMOBILE:
            $this->_instance->eltdiff->track = true;
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
            $session->set('imp', self::STORAGE_KEY, $this->_instance);
        }
    }

}
