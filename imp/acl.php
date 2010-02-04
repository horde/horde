<?php
/**
 * ACL (Access Control List) administration page.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Hastie <imp@oak-wood.co.uk>
 * @author  Eric Garrido <ekg2002@columbia.edu>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

/* Redirect back to the options screen if ACL is not enabled. */
$prefs_url = Horde::getServiceLink('options', 'imp');
if ($prefs->isLocked('acl') || empty($_SESSION['imp']['imap']['acl'])) {
    $notification->push('Folder sharing is not enabled.', 'horde.error');
    header('Location: ' . $prefs_url);
    exit;
}

try {
    $ACL = $injector->getInstance('IMP_Imap_Acl');
} catch (Horde_Exception $e) {
    $notification->push(_("This server does not support sharing folders."), 'horde.error');
    header('Location: ' . $prefs_url);
    exit;
}

$vars = Horde_Variables::getDefaultVariables();

/* Check to see if $vars->new_user already has an acl on the folder. */
if ($vars->new_user && isset($vars->acl[$vars->new_user])) {
    $vars->acl[$vars->new_user] = $vars->new_acl;
    $vars->new_user = '';
}

$protected = $ACL->getProtected();

/* Run through the action handlers. */
switch ($vars->actionID) {
case 'imp_acl_set':
    if (!$vars->folder) {
        $notification->push(_("No folder selected."), 'horde.error');
        break;
    }

    if ($vars->new_user) {
        try {
            $ACL->editACL($vars->folder, $vars->new_user, $vars->new_acl);
            if (count($vars->new_acl)) {
                $notification->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $vars->new_user, $vars->folder), 'horde.success');
            } else {
                $notification->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $vars->folder, $vars->new_user), 'horde.success');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }

    $curr_acl = $ACL->getACL($vars->folder);
    foreach ($vars->acl as $user => $acl) {
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
        if ((isset($curr_acl[$user])) &&
            (array_keys($curr_acl[$user]) == array_keys($acl))) {
            continue;
        }

        try {
            unset($curr_acl);
            $ACL->editACL($vars->folder, $user, $acl);
            if (!count($acl)) {
                $notification->push(sprintf(_("All rights on folder \"%s\" successfully removed for user \"%s\"."), $vars->folder, $user), 'horde.success');
            } else {
                $notification->push(sprintf(_("User \"%s\" successfully given the specified rights for the folder \"%s\"."), $user, $vars->folder), 'horde.success');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;
}

$imp_folder = $injector->getInstance('IMP_Folder');
$rights = $ACL->getRights();

if (empty($vars->folder)) {
    $vars->folder = 'INBOX';
}

if (!isset($curr_acl)) {
    $curr_acl = $ACL->getACL($vars->folder);
}
$canEdit = $ACL->canEdit($vars->folder, Horde_Auth::getAuth());

$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_Ui::generateHeader('imp', null, null, $chunk);

/* Set up template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('aclurl', Horde::applicationUrl('acl.php'));
$t->set('forminput', Horde_Util::formInput());
$t->set('aclnavcell', Horde_Util::bufferOutput(array('Horde_Prefs_Ui', 'generateNavigationCell'), 'imp', 'acl'));
$t->set('changefolder', Horde::link('#', _("Change Folder"), 'smallheader', '', '', '', '', array('id' => 'changefolder')));
$t->set('options', IMP::flistSelect(array('selected' => $vars->folder)));
$t->set('current', sprintf(_("Current access to %s"), IMP::displayFolder($vars->folder)));
$t->set('folder', $vars->folder);
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
            $entry['rule'][] = array('val' => $val, 'enabled' => in_array($val, $rule));
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
        'desc' => $val['desc'],
        'title' => $val['title']
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
