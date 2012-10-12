<?php
/**
 * Turba contact.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$vars = Horde_Variables::getDefaultVariables();
$source = $vars->get('source');
if (!isset($GLOBALS['cfgSources'][$source])) {
    $notification->push(_("The contact you requested does not exist."));
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

/* Set the contact from the key requested. */
try {
    $driver = $injector->getInstance('Turba_Factory_Driver')->create($source);
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

$contact = null;
$uid = $vars->get('uid');
if (!empty($uid)) {
    try {
        $search = $driver->search(array('__uid' => $uid));
        if (count($search)) {
            $contact = $search->next();
            $vars->set('key', $contact->getValue('__key'));
        }
    } catch (Turba_Exception $e) {}
}
if (!$contact) {
    try {
        $contact = $driver->getObject($vars->get('key'));
    } catch (Turba_Exception $e) {
        $notification->push($e, 'horde.error');
        Horde::url($prefs->getValue('initial_page'), true)->redirect();
    }
}

// Mark this contact as the user's own?
if ($vars->get('action') == 'mark_own') {
    $prefs->setValue('own_contact', $source . ';' . $contact->getValue('__key'));
    $notification->push(_("This contact has been marked as your own."), 'horde.success');
}

// Get view.
$viewName = Horde_Util::getFormData('view', 'Contact');
switch ($viewName) {
case 'Contact':
    $view = new Turba_View_Contact($contact);
    if (!$vars->get('url')) {
        $vars->set('url', $contact->url(null, true));
    }
    break;

case 'EditContact':
    $view = new Turba_View_EditContact($contact);
    break;

case 'DeleteContact':
    $view = new Turba_View_DeleteContact($contact);
    break;
}

// Get tabs.
$url = $contact->url();
$tabs = new Horde_Core_Ui_Tabs('view', $vars);
$tabs->addTab(_("_View"), $url,
              array('tabname' => 'Contact',
                    'id' => 'tabContact',
                    'class' => 'horde-icon',
                    'onclick' => 'return ShowTab(\'Contact\');'));
if ($contact->hasPermission(Horde_Perms::EDIT)) {
    $tabs->addTab(_("_Edit"), $url,
                  array('tabname' => 'EditContact',
                        'id' => 'tabEditContact',
                        'class' => 'horde-icon',
                        'onclick' => 'return ShowTab(\'EditContact\');'));
}
if ($contact->hasPermission(Horde_Perms::DELETE)) {
    $tabs->addTab(_("De_lete"), $url,
                  array('tabname' => 'DeleteContact',
                        'id' => 'tabDeleteContact',
                        'class' => 'horde-icon',
                        'onclick' => 'return ShowTab(\'DeleteContact\');'));
}

@list($own_source, $own_id) = explode(';', $prefs->getValue('own_contact'));
if ($own_source == $source && $own_id == $contact->getValue('__key')) {
    $own_icon = ' ' . Horde::img('user.png', _("Your own contact"),
                                 array('title' => _("Your own contact")));
    $own_link = '';
} else {
    $own_icon = '';
    $own_link = '<span class="smallheader rightFloat">'
        . $url->copy()->add('action', 'mark_own')->link()
        . _("Mark this as your own contact") . '</a></span>';
}

$page_output->addScriptFile('contact_tabs.js');
$page_output->header(array(
    'title' => $view->getTitle()
));
$notification->notify(array('listeners' => 'status'));
echo '<div id="page">';
echo $tabs->render($viewName, 'horde-buttonbar');
echo '<h1 class="header">' . $own_link
    . ($contact->getValue('name')
       ? htmlspecialchars($contact->getValue('name'))
       : '<em>' . _("Blank name") . '</em>')
    . $own_icon . '</h1>';
$view->html();
echo '</div>';
$page_output->footer();
