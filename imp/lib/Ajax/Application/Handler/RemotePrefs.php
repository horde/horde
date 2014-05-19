<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used on the remote accounts preference page.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_RemotePrefs
extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Do autoconfiguration for a remote account.
     *
     * Variables used:
     *   - email: (string) The e-mail address.
     *   - password: (string) Remote server password.
     *   - password_base64: (boolean) If true, password is base64 encoded.
     *   - secure: (boolean) If true, require a secure remote connection.
     *
     * @return boolean  An object with the following properties:
     *   - mconfig: (object) The configuration object.
     *   - success: (boolean) True if autoconfiguration was successful.
     */
    public function autoconfigAccount()
    {
        global $injector, $notification;

        $res = new stdClass;
        $res->success = false;

        $password = $this->vars->password;
        if ($this->vars->password_base64) {
            $password = base64_decode($password);
        }

        try {
            $aconfig = $injector->getInstance('IMP_Mail_Autoconfig');
            $mconfig = $aconfig->getMailConfig($this->vars->email, array(
                'auth' => $password,
                'insecure' => empty($this->vars->secure)
            ));

            if ($mconfig && !is_null($mconfig->username)) {
                $email = new Horde_Mail_Rfc822_Address($this->vars->email);
                $imap = ($mconfig instanceof Horde_Mail_Autoconfig_Server_Imap);

                $res->mconfig = (object)$mconfig;
                $res->mconfig->imap = $imap;
                if (!strlen($res->mconfig->label)) {
                    $res->mconfig->label = $email->bare_address;
                }
                $res->success = true;

                $notification->push(
                    _("Automatic configuration of the account was successful."),
                    'horde.success'
                );
            }
        } catch (Horde_Mail_Autoconfig_Exception $e) {}

        if (!$res->success) {
            $notification->push(
                _("Automatic configuration of the account failed. Please check your settings or otherwise use the Advanced Setup to manually enter the remote server configuration."),
                'horde.error'
            );
        }

        return $res;
    }

}
