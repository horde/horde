<?php
/**
 * ACL (Access Control List) administration page.
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Hastie <imp@oak-wood.co.uk>
 * @author  Eric Garrido <ekg2002@columbia.edu>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

/* Redirect back to the options screen if ACL is not enabled. */
$prefs_url = Horde::getServiceLink('options', 'imp');
if ($prefs->isLocked('acl') || empty($_SESSION['imp']['acl'])) {
    $notification->push(_("Folder sharing is not enabled."), 'horde.error');
    header('Location: ' . $prefs_url);
    exit;
}

try {
    $ACLDriver = IMP_Imap_Acl::singleton();
} catch (Horde_Exception $e) {
    $notification->push($error, _("This server does not support sharing folders."));
    header('Location: ' . $prefs_url);
    exit;
}

$acls = Horde_Util::getFormData('acl');
$folder = Horde_Util::getFormData('folder');
$new_user = Horde_Util::getFormData('new_user');
if ($new_user) {
    $new_acl = Horde_Util::getFormData('new_acl');
    /* check to see if $new_user already has an acl on the folder */
    if (isset($acls[$new_user])) {
        $acls[$new_user] = $new_acl;
        $new_user = '';
    }
}

$protected = $ACLDriver->getProtected();

/* Run through the action handlers. */
$ok_form = true;
switch (Horde_Util::getFormData('actionID')) {
case 'imp_acl_set':
    if (!$folder) {
        $notification->push(_("No folder selected."), 'horde.error');
        $ok_form = false;
    }

    if ($new_user) {
        /* Each ACL is submitted with the acl as the value. Reverse the hash
           mapping for editACL(). */
        $new_acl = array_flip($new_acl);
        try {
            $ACLDriver->editACL($folder, $new_user, $new_acl);
            if (!count($new_acl)) {
                $notification->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $folder, $new_user), 'horde.success');
            } else {
                $notification->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $new_user, $folder), 'horde.success');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }

    if ($ok_form) {
        $current_acl = $ACLDriver->getACL($folder);
        foreach ($acls as $user => $acl) {
            if ($acl) {
                $acl = array_flip($acl);
                /* We had to have an empty value submitted to make sure all
                   users with acls were sent back, so we can remove those
                   without checkmarks. */
                unset($acl['']);
            } else {
                $acl = array();
            }

            if (!$user) {
                $notification->push(_("No user specified."), 'horde.error');
                continue;
            }

            if (in_array($user, $protected)) {
                if ($acl) {
                    $notification->push(sprintf(_("Rights for user \"%s\" cannot be modified."), $user), 'horde.error');
                }
                continue;
            }

            /* Check to see if ACL didn't change */
            if ((isset($current_acl[$user])) &&
                (array_keys($current_acl[$user]) == array_keys($acl))) {
                continue;
            }

            try {
                $ACLDriver->editACL($folder, $user, $acl);
                if (!count($acl)) {
                    $notification->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $folder, $user), 'horde.success');
                } else {
                    $notification->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $user, $folder), 'horde.success');
                }
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }
    }
    break;
}

$imp_folder = IMP_Folder::singleton();
$rights = $ACLDriver->getRights();

if (empty($folder)) {
    $folder = 'INBOX';
}

$curr_acl = $ACLDriver->getACL($folder);
$canEdit = $ACLDriver->canEdit($folder, Horde_Auth::getAuth());

$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_Ui::generateHeader('imp', null, null, $chunk);

/* Set up template. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('aclurl', Horde::applicationUrl('acl.php'));
$t->set('forminput', Horde_Util::formInput());
$t->set('aclnavcell', Horde_Util::bufferOutput(array('Horde_Prefs_Ui', 'generateNavigationCell'), 'imp', 'acl'));
$t->set('changefolder', Horde::link('#', _("Change Folder"), 'smallheader', '', '', '', '', array('id' => 'changefolder')));
$t->set('sharedimg', Horde::img('shared.png', _("Change Folder")));
$t->set('options', IMP::flistSelect(array('selected' => $folder)));
$t->set('current', sprintf(_("Current access to %s"), IMP::displayFolder($folder)));
$t->set('folder', $folder);
$t->set('noacl', !count($curr_acl));
$t->set('maxrule', 1);
if (!$t->get('noacl')) {
    $i = 0;
    $cval = array();
    foreach ($curr_acl as $index => $rule) {
        $entry = array(
            'i' => ++$i,
            'num_val' => ($i - 1),
            'disabled' => in_array($index, $protected) || !$canEdit,
            'index' => $index
        );
        /* Create table of each ACL option for each user granted permissions,
         * enabled indicates the right has been given to the user */
        foreach (array_keys($rights) as $val) {
            $entry['rule'][] = array('val' => $val, 'enabled' => isset($rule[$val]));
        }
        $cval[] = $entry;
    }
    $t->set('curr_acl', $cval);
    $t->set('maxval', count($curr_acl));
    /* number of individual ACL options, for table rendering */
    $t->set('maxrule', count($rights));
}
$t->set('canedit', $canEdit);
if (empty($_SESSION['imp']['admin'])) {
    $new_user_field = '<input id="new_user" type="text" name="new_user"/>';
} else {
    $current_users = array_keys($curr_acl);
    $new_user_field = '<select id="new_user" name="new_user">';
    foreach ($registry->callByPackage('userList', 'imp') as $user) {
        if (in_array($user, $current_users)) {
            continue;
        }
        $new_user_field .= "\n<option>" . htmlspecialchars($user) . '</option>';
    }
    $new_user_field .= "\n</select>";
}
$t->set('new_user', $new_user_field);

$rightsTitlesval = array();
foreach ($rights as $right => $val) {
    $rightsval[] = array(
        'right' => $right,
        'desc' => $val['desc']
    );
}

$t->set('rights', $rightsval);
$t->set('width', round(100 / (count($rightsval) + 1)) . '%');
$t->set('prefsurl', $prefs_url);

Horde::addScriptFile('acl.js', 'imp');
echo $t->fetch(IMP_TEMPLATES . '/acl/acl.html');
if (!$chunk) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
