<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'remotemanagement' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_Remote implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output;

        $ui->nobuttons = true;

        $page_output->addScriptFile('external/base64.js');
        $page_output->addScriptFile('remoteprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpRemotePrefs.confirm_delete' => _("Are you sure you want to delete this account?"),
            'ImpRemotePrefs.empty_email' => _("The e-mail field cannot be empty."),
            'ImpRemotePrefs.empty_password' => _("The password field cannot be empty."),
            'ImpRemotePrefs.next' => _("Next"),
            'ImpRemotePrefs.wait' => _("Please wait...")
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Text');

        switch ($ui->vars->remote_action) {
        case 'new':
            $view->new = true;
            break;

        default:
            $view->accounts = iterator_to_array($injector->getInstance('IMP_Remote'));
            break;
        }

        return $view->render('remote');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        $remote = $injector->getInstance('IMP_Remote');

        switch ($ui->vars->remote_action) {
        case 'add':
            try {
                $ob = new IMP_Remote_Account();
                $ob->hostspec = $ui->vars->remote_server;
                $ob->username = $ui->vars->remote_user;
                if (strlen($ui->vars->remote_label)) {
                    $ob->label = $ui->vars->remote_label;
                }
                if ($ui->vars->remote_port) {
                    $ob->port = $ui->vars->remote_port;
                }
                if ($ui->vars->get('remote_type') == 'pop3') {
                    $ob->type = $ob::POP3;
                }

                if (isset($ui->vars->remote_secure_autoconfig)) {
                    switch ($ui->vars->remote_secure_autoconfig) {
                    case 'starttls':
                        $ob->secure = 'tls';
                        break;

                    case 'tls':
                        $ob->secure = 'ssl';
                        break;

                    default:
                        $ob->secure = true;
                        break;
                    }
                } else {
                    switch ($ui->vars->remote_secure) {
                    case 'auto':
                        $ob->secure = true;
                        break;

                    case 'yes':
                        switch ($ob->type) {
                        case $ob::IMAP:
                            $tmp = new Horde_Mail_Autoconfig_Server_Imap();
                            break;

                        case $ob::POP3:
                            $tmp = new Horde_Mail_Autoconfig_Server_Pop3();
                            break;
                        }

                        $tmp->host = $ob->hostspec;
                        $tmp->port = $ob->port;
                        $tmp->tls = 'tls';

                        $ob->secure = $tmp->valid()
                            ? 'ssl'
                            : 'tls';
                    }
                    break;
                }

                $remote[strval($ob)] = $ob;

                $notification->push(sprintf(_("Account \"%s\" added."), $ui->vars->remote_server), 'horde.success');

                $injector->getInstance('IMP_Ftree')->insert($ob);
            } catch (IMP_Exception $e) {
                $notification->push($e, 'horde.error');
            }
            break;

        case 'delete':
            if (isset($remote[$ui->vars->remote_data])) {
                $ob = $remote[$ui->vars->remote_data];
                unset($remote[$ui->vars->remote_data]);
                $notification->push(sprintf(_("Account \"%s\" deleted."), $ob->label), 'horde.success');

                $injector->getInstance('IMP_Ftree')->delete($ob);
            }
            break;
        }

        return false;
    }

}
