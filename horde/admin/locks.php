<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:locks')
));

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
    $page_output->addScriptFile('tables.js', 'horde');
} catch (Horde_Lock_Exception $e) {
    $view->locks = array();
    $view->error = sprintf(_("Listing locks failed: %s"), $e->getMessage());
}

$page_output->header(array(
    'title' => _("Locks")
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('list');
$page_output->footer();
