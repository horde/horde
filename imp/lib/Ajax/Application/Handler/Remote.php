<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used for remote server access.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Remote extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Create mailbox select list for advanced search page.
     *
     * Variables used:
     *   - password: (string) Remote server password.
     *   - password_base64: (boolean) If true, password is base64 encoded.
     *   - remoteid: (string) Remote server ID (base64url encoded).
     *
     * @return boolean  An object with the following properties:
     *   - success: (boolean) True if login was successful.
     */
    public function remoteLogin()
    {
        global $injector, $notification;

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

            $imp_imap_tree = $injector->getInstance('IMP_Imap_Tree');
            $imp_imap_tree->delete($remote_ob);
            $imp_imap_tree->insert($remote_ob);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Could not authenticate to %s."), $remote_ob->label), 'horde.error');
        }

        return $res;
    }

}
