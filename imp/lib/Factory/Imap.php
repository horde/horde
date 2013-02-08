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
class IMP_Factory_Imap extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $session;

        try {
            $ob = $session->get('imp', 'imap_ob');
        } catch (Exception $e) {
            // This indicates an unserialize() error.  This is fatal, so
            // logout.
            throw new Horde_Exception_AuthenticationFailure('', Horde_Auth::REASON_SESSION);
        }

        if (!$ob) {
            $ob = new IMP_Imap();

            /* Explicitly save object when first creating. Prevents losing
             * authentication information in case a misconfigured server
             * crashes before shutdown operations can occur. */
            $session->set('imp', 'imap_ob', $ob);
        }

        register_shutdown_function(array($this, 'shutdown'), $ob);

        return $ob;
    }

    /**
     * Saves IMP_Imap instance to the session on shutdown.
     *
     * @param IMP_Imap $ob  Imap object.
     */
    public function shutdown($ob)
    {
        if ($ob->changed && ($GLOBALS['registry']->getAuth() !== false)) {
            $GLOBALS['session']->set('imp', 'imap_ob', $ob);
        }
    }

}
