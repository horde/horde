<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'remotemanagement' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
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

        $page_output->addScriptFile('remoteprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpRemotePrefs.confirm_delete' => _("Are you sure you want to delete this account?")
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
            $out = array();
            foreach ($injector->getInstance('IMP_Remote') as $key => $val) {
                $out[] = array(
                    'id' => $key,
                    'port' => $val['port'],
                    'secure' => ($val['secure'] === true),
                    'secure_auto' => !isset($val['secure']),
                    'server' => $val['server'],
                    'type' => $val['type'],
                    'user' => $val['user'],
                );
            }
            $view->accounts = $out;
        }

        return $view->render('remote');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        $remote = $injector->getInstance('IMP_Remote');
        $success = false;

        switch ($ui->vars->remote_action) {
        case 'add':
            try {
                $secure = $ui->vars->remote_secure;
                $remote[strval(new Horde_Support_Randomid())] = array(
                    'port' => $ui->vars->remote_port,
                    'secure' => (($secure == 'auto') ? null : ($secure == 'yes')),
                    'server' => $ui->vars->remote_server,
                    'type' => $ui->vars->get('remote_type', 'imap'),
                    'user' => $ui->vars->remote_user
                );

                $notification->push(sprintf(_("Account \"%s\" added."), $ui->vars->remote_server), 'horde.success');
                $success = true;
            } catch (IMP_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
            }
            break;

        case 'delete':
            if (isset($remote[$ui->vars->remote_data])) {
                $tmp = $remote[$ui->vars->remote_data];
                unset($remote[$ui->vars->remote_data]);
                $notification->push(sprintf(_("Account \"%s\" deleted."), $tmp['server']), 'horde.success');
                $success = true;
            }
            break;
        }

        if ($success) {
            $injector->getInstance('IMP_Imap_Tree')->init();
        }

        return false;
    }

}
