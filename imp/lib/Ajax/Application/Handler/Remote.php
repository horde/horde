<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used for remote server access.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Remote extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Login to a remote account.
     *
     * Variables used:
     *   - password: (string) Remote server password.
     *   - password_base64: (boolean) If true, password is base64 encoded.
     *   - remoteid: (string) Remote server ID (base64url encoded).
     *   - unsub: (boolean) If true, show unsubscribed mailboxes.
     *
     * @return boolean  An object with the following properties:
     *   - success: (boolean) True if login was successful.
     */
    public function remoteLogin()
    {
        global $injector, $notification, $prefs;

        $remote = $injector->getInstance('IMP_Remote');
        $remoteid = IMP_Mailbox::formFrom($this->vars->remoteid);

        $res = new stdClass;
        $res->success = false;

        if (!isset($remote[$remoteid])) {
            $notification->push(_("Could not find remote server configuration."), 'horde.error');
            return $res;
        }

        $password = $this->vars->password;
        if ($this->vars->password_base64) {
            $password = base64_decode($password);
        }

        $remote_ob = $remote[$remoteid];

        try {
            $remote_ob->createImapObject($password);
            $remote_ob->imp_imap->login();
            $res->success = true;
            $notification->push(sprintf(_("Successfully authenticated to %s."), $remote_ob->label), 'horde.success');

            $ftree = $injector->getInstance('IMP_Ftree');

            $ftree->delete($remote_ob);
            $ftree->insert($remote_ob);

            $ftree[$remote_ob]->open = true;
            $this->_base->queue->setMailboxOpt('expand', 1);

            $iterator = new IMP_Ftree_IteratorFilter(
                new IMP_Ftree_Iterator($ftree[$remote_ob])
            );
            if ($this->vars->unsub) {
                $ftree->loadUnsubscribed();
                $iterator->remove($iterator::UNSUB);
            }

            switch ($prefs->getValue('nav_expanded')) {
            case IMP_Ftree_Prefs_Expanded::NO:
                $iterator->add($iterator::CHILDREN);
                break;

            case IMP_Ftree_Prefs_Expanded::LAST:
                $iterator->add($iterator::EXPANDED);
                break;
            }

            array_map(
                array($ftree->eltdiff, 'add'),
                iterator_to_array($iterator, false)
            );
        } catch (Exception $e) {
            $notification->push(sprintf(_("Could not authenticate to %s."), $remote_ob->label), 'horde.error');
        }

        return $res;
    }

    /**
     * AJAX action: Logout from a remote account.
     *
     * Variables used:
     *   - remoteid: (string) Remote server ID (base64url encoded).
     *
     * @return boolean  True.
     */
    public function remoteLogout()
    {
        global $injector, $notification;

        $remote = $injector->getInstance('IMP_Remote');
        $remoteid = IMP_Mailbox::formFrom($this->vars->remoteid);
        $remote_ob = $remote[$remoteid];

        $injector->getInstance('IMP_Factory_Imap')->destroy($remoteid);

        $ftree = $injector->getInstance('IMP_Ftree');
        $ftree->delete($remote_ob);
        $ftree->insert($remote_ob);

        $notification->push(sprintf(_("Logged out of %s."), $remote_ob->label), 'horde.success');

        return true;
    }

}
