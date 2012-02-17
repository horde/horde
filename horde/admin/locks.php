<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$permission = 'locks';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() &&
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:' . $permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

$horde_lock = $injector->getInstance('Horde_Lock');

if ($lock = Horde_Util::getFormData('unlock')) {
    try {
        $horde_lock->clearLock($lock);
        $notification->push(_("The lock has been removed."), 'horde.success');
    } catch (Horde_Lock_Exception $e) {
        $notification->push($e);
    }
}

$view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/admin/locks'));
new Horde_View_Helper_Text($view);

try {
    $format = $prefs->getValue('date_format') . ' ' . $prefs->getValue('time_format');
    $locks = $horde_lock->getLocks();
    $url = Horde::url('admin/locks.php');
    foreach ($locks as &$lock) {
        $lock['unlock_link'] = $url->copy()
            ->add('unlock', $lock['lock_id'])
            ->link()
            . _("Unlock")
            . '</a>';
        if ($appname = $registry->get('name', $lock['lock_scope'])) {
            $lock['scope'] = $appname;
        } else {
            $lock['scope'] = $lock['lock_scope'];
        }
        $lock['start'] = strftime($format, $lock['lock_update_timestamp']);
        $lock['end'] = strftime($format, $lock['lock_expiry_timestamp']);
    }
    $view->locks = $locks;
    Horde::addScriptFile('tables.js', 'horde');
} catch (Horde_Lock_Exception $e) {
    $view->locks = array();
    $view->error = sprintf(_("Listing locks failed: %s"), $e->getMessage());
}

$title = _("Locks");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('list');

require HORDE_TEMPLATES . '/common-footer.inc';
