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
 * A Horde_Injector based IMP_Imap factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Imap extends Horde_Core_Factory_Injector implements Horde_Queue_Task
{
    /**
     * @var IMP_Imap
     */
    private $_instance;

    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $session;

        try {
            $this->_instance = $session->get('imp', 'imap_ob');
        } catch (Exception $e) {
            // This indicates an unserialize() error.  This is fatal, so
            // logout.
            throw new Horde_Exception_AuthenticationFailure('', Horde_Auth::REASON_SESSION);
        }

        if (!$this->_instance) {
            $this->_instance = new IMP_Imap();

            /* Explicitly save object when first creating. Prevents losing
             * authentication information in case a misconfigured server
             * crashes before shutdown operations can occur. */
            $session->set('imp', 'imap_ob', $this->_instance);
        }

        $injector->getInstance('Horde_Queue_Storage')->add($this);

        return $this->_instance;
    }

    /**
     * Saves IMP_Imap instance to the session on shutdown.
     */
    public function run()
    {
        global $registry, $session;

        if ($this->_instance->changed && ($registry->getAuth() !== false)) {
            $session->set('imp', 'imap_ob', $this->_instance);
        }
    }

}
