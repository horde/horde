<?php
/**
 * Preferences UI page.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 */

function _loadPrefsConfig($app, $merge = false)
{
    try {
        $res = Horde::loadConfiguration('prefs.php', array('prefGroups', '_prefs'), $app);
    } catch (Horde_Exception $e) {
        $res = array('prefGroups' => array(), '_prefs' => array());
    }

    if ($merge) {
        try {
            $GLOBALS['prefGroups']['identities']['members'] = array_keys(array_flip(array_merge(
                $res['prefGroups']['identities']['members'],
                $GLOBALS['prefGroups']['identities']['members'])));
            $GLOBALS['_prefs'] = Horde_Array::array_merge_recursive_overwrite($res['_prefs'], $GLOBALS['_prefs']);
        } catch (Horde_Exception $e) {}
    } else {
        $GLOBALS['prefGroups'] = $res['prefGroups'];
        $GLOBALS['_prefs'] = $res['_prefs'];
    }
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde');

/* Figure out which application we're setting preferences for. */
$app = Horde_Util::getFormData('app', Horde_Prefs_Ui::getDefaultApp());
$appbase = realpath($registry->get('fileroot', $app));

/* See if we have a preferences group set. */
$group = Horde_Util::getFormData('group');

/* See if only a page body was requested. */
$chunk = Horde_Util::nonInputVar('chunk');

/* Load $app's base environment. */
$registry->pushApp($app);

/* Set title. */
$title = sprintf(_("Options for %s"), $registry->get('name'));

/* Load identity here - Identity object may be needed in app's prefs.php. */
if ($group == 'identities') {
    $identity = Horde_Prefs_Identity::singleton($app == 'horde' ? null : array($app, $app));
}

/* Get ActionID. */
$actionID = Horde_Util::getFormData('actionID');

/* Run prefs_ui init code, if available. */
if ($registry->hasAppMethod($app, 'prefsInit')) {
    $result = $registry->callAppMethod($app, 'prefsInit', array('args' => array($group)));
    if (!empty($result)) {
        extract($result);
    }
}

/* Load $app's preferences, if any. */
$prefGroups = array();
_loadPrefsConfig($app);

/* See if this group has a custom URL. */
if ($group && !empty($prefGroups[$group]['url'])) {
    $pref_url = $prefGroups[$group]['url'];
    $filename = $appbase . '/' . $pref_url;
    if (file_exists($filename)) {
        require $filename;
        return;
    }
    throw new Horde_Exception('Incorrect url value (' . $pref_url . ') for preferences group ' . $group . ' for app ' . $app);
}

/* If there's only one prefGroup, just show it. */
if (empty($group) && count($prefGroups) == 1) {
    $group = array_keys($prefGroups);
    $group = array_pop($group);
}

if ($group == 'identities') {
    if ($app != 'horde') {
        _loadPrefsConfig('horde', true);
    }

    switch ($actionID) {
    case 'update_prefs':
        $from_addresses = $identity->getAll('from_addr');
        $current_from = $identity->getValue('from_addr');
        if ($prefs->isLocked('default_identity')) {
            $default = $identity->getDefault();
        } else {
            $default = Horde_Util::getPost('default_identity');
            $id = Horde_Util::getPost('identity');
            if ($id == -1) {
                $id = $identity->add();
            } elseif ($id == -2) {
                $prefGroups['identities']['members'] = array('default_identity');
            }
            $identity->setDefault($id);
        }

        if (!Horde_Prefs_Ui::handleForm($group, $identity, $app, $prefGroups, $_prefs)) {
            break;
        }

        $new_from = $identity->getValue('from_addr');
        if (!empty($conf['user']['verify_from_addr']) &&
            $current_from != $new_from &&
            !in_array($new_from, $from_addresses)) {
            try {
                $result = $identity->verifyIdentity($id, empty($current_from) ? $new_from : $current_from);
                if ($result instanceof Notification_Event) {
                    $notification->push($result, 'horde.message');
                }
            } catch (Horde_Exception $e) {
                $notification->push(_("The new from address can't be verified, try again later: ") . $e->getMessage(), 'horde.error');
                Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
            break;
        }

        $identity->setDefault($default);
        $identity->save();
        unset($prefGroups);

        _loadPrefsConfig($app);
        if ($app != 'horde') {
            _loadPrefsConfig('horde', true);
        }
        break;

    case 'delete_identity':
        $id = (int)Horde_Util::getFormData('id');
        $deleted_identity = $identity->delete($id);
        unset($_prefs['default_identity']['enum'][$id]);
        $notification->push(sprintf(_("The identity \"%s\" has been deleted."), $deleted_identity[0]['id']), 'horde.success');
        break;

    case 'change_default_identity':
        $default_identity = $identity->setDefault(Horde_Util::getFormData('id'));
        $identity->save();
        $notification->push(_("Your default identity has been changed."),
                            'horde.success');
        break;
    }
} elseif ($group &&
          ($actionID == 'update_prefs') &&
          Horde_Prefs_Ui::handleForm($group, $prefs, $app, $prefGroups, $_prefs)) {
    _loadPrefsConfig($app);

    if (count($prefGroups) == 1 && empty($group)) {
        $group = array_keys($prefGroups);
        $group = array_pop($group);
    }
}

/* Show the UI. */
Horde_Prefs_Ui::generateUI($app, $prefGroups, $_prefs, $group, $chunk);

if (!$chunk) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
