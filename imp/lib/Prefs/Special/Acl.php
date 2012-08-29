<?php
/**
 * Special prefs handling for the 'aclmanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Special_Acl implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $notification, $page_output, $registry, $session;

        $page_output->addScriptFile('acl.js');

        $acl = $injector->getInstance('IMP_Imap_Acl');

        $mbox = isset($ui->vars->mbox)
            ? IMP_Mailbox::formFrom($ui->vars->mbox)
            : IMP_Mailbox::get('INBOX');

        try {
            $curr_acl = $acl->getACL($mbox);
        } catch (IMP_Exception $e) {
            $notification->push($e);
            $curr_acl = array();
        }

        if (!($canEdit = $acl->canEdit($mbox))) {
            $notification->push(_("You do not have permission to change access to this mailbox."), 'horde.warning');
        }

        $rightslist = $acl->getRights();

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('options', IMP::flistSelect(array(
            'basename' => true,
            'selected' => $mbox
        )));
        $t->set('current', sprintf(_("Current access to %s"), $mbox->display_html));
        $t->set('mbox', $mbox->form_to);
        $t->set('hasacl', count($curr_acl));

        if ($t->get('hasacl')) {
            $cval = array();

            foreach ($curr_acl as $index => $rule) {
                $entry = array(
                    'index' => htmlspecialchars($index),
                    'rule' => array()
                );

                if ($rule instanceof Horde_Imap_Client_Data_AclNegative) {
                    $entry['negative'] = htmlspecialchars(substr($index, 1));
                }

                /* Create table of each ACL option for each user granted
                 * permissions; enabled indicates the right has been given to
                 * the user. */
                $rightsmbox = $acl->getRightsMbox($mbox, $index);
                foreach (array_keys($rightslist) as $val) {
                    $entry['rule'][] = array(
                        'disable' => !$canEdit || !$rightsmbox[$val],
                        'on' => $rule[$val],
                        'val' => $val
                    );
                 }
                 $cval[] = $entry;
             }

             $t->set('curr_acl', $cval);
        }

        $t->set('canedit', $canEdit);

        if ($session->get('imp', 'imap_admin')) {
            $current_users = array_keys($curr_acl);
            $new_user = array();

            try {
                foreach (array('anyone') + $registry->callAppMethod('imp', 'authUserList') as $user) {
                    if (!in_array($user, $current_users)) {
                        $new_user[] = htmlspecialchars($user);
                    }
                }
            } catch (Horde_Exception $e) {
                $notification->push($e);
                return;
            }
            $t->set('new_user', $new_user);
        } else {
            $t->set('noadmin', true);
        }

        $rights = array();
        foreach ($rightslist as $key => $val) {
            $val['val'] = $key;
            $rights[] = $val;
        }
        $t->set('rights', $rights);

        $t->set('width', round(100 / (count($rights) + 1)) . '%');

        return $t->fetch(IMP_TEMPLATES . '/prefs/acl.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if ($ui->vars->change_acl_mbox) {
            return false;
        }

        $acl = $injector->getInstance('IMP_Imap_Acl');
        $mbox = IMP_Mailbox::formFrom($ui->vars->mbox);

        try {
            $curr_acl = $acl->getACL($mbox);
        } catch (IMP_Exception $e) {
            $notification->push($e);
            return;
        }

        if (!($acl_list = $ui->vars->acl)) {
            $acl_list = array();
        }
        $new_user = $ui->vars->new_user;

        if (strlen($new_user) && $ui->vars->new_acl) {
            if (isset($acl_list[$new_user])) {
                $acl_list[$new_user] = $ui->vars->new_acl;
            } else {
                try {
                    $acl->addRights($mbox, $new_user, implode('', $ui->vars->new_acl));
                    $notification->push(sprintf(_("ACL for \"%s\" successfully created for the mailbox \"%s\"."), $new_user, $mbox->label), 'horde.success');
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
            }
        }

        foreach ($curr_acl as $index => $rule) {
            if (isset($acl_list[$index])) {
                /* Check to see if ACL changed, but only compare rights we
                 * understand. */
                $acldiff = $rule->diff(implode('', $acl_list[$index]));
                $update = false;

                try {
                    if ($acldiff['added']) {
                        $acl->addRights($mbox, $index, $acldiff['added']);
                        $update = true;
                    }
                    if ($acldiff['removed']) {
                        $acl->removeRights($mbox, $index, $acldiff['removed']);
                        $update = true;
                    }

                    if ($update) {
                        $notification->push(sprintf(_("ACL rights for \"%s\" updated for the mailbox \"%s\"."), $index, $mbox->label), 'horde.success');
                    }
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
            } else {
                /* If we dont see ANY form params, the user deleted all
                 * rights. */
                try {
                    $acl->removeRights($mbox, $index, null);
                    $notification->push(sprintf(_("All rights on mailbox \"%s\" successfully removed for \"%s\"."), $mbox->label, $index), 'horde.success');
                } catch (IMP_Exception $e) {
                    $notification->push($e);
                }
            }
        }

        return false;
    }

}
