<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'tabs.php';

$title = _("Privacy");

$profile = $folks_driver->getRawProfile($GLOBALS['registry']->getAuth());
if ($profile instanceof PEAR_Error) {
    $notification->push($profile);
    Folks::getUrlFor('list', 'list')->redirect();
}

$statuses = array('public' => _("Public"),
                'public_authenticated' => _("Public - only authenticated users can see my personal data"),
            //   'public_private' => _("Public - others can see only ma basic data"),
                'public_friends' => _("Public - only my friends can see my presonal data"),
                'private' => _("Private"));

$types = array('all' => _("All visitors"),
                'authenticated' => _("Only authenticated users"),
                'friends' => _("Only my friedns"),
                'noone' => _("No one"));

$form = new Horde_Form($vars, $title, 'privacy');

$v = &$form->addVariable(_("Status"), 'user_status', 'radio', true, false, null, array($statuses));
$v->setDefault($profile['user_status']);

$v = &$form->addVariable(_("Who can see when I was last time online"), 'last_online', 'radio', false, false, null, array($types));
$v->setDefault($profile['last_online']);

$v = &$form->addVariable(_("Who can see my acticity log on my profile"), 'activity_log', 'radio', false, false, null, array($types));
$v->setDefault($profile['activity_log']);

$v = &$form->addVariable(_("Notify online friends that I logged in"), 'login_notify', 'radio', false, false, null, array(array(_("No"), _("Yes"))));
$v->setDefault($prefs->getValue('login_notify'));

if ($form->validate()) {

    $form->getInfo(null, $info);

    // Save pref
    $prefs->setValue('login_notify', $info['login_notify']);

    // Save profile
    unset($info['login_notify']);
    $result = $folks_driver->saveProfile($info);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Your data were successfully updated."), 'horde.success');
        Horde::url('edit/privacy.php')->redirect();
    }

}

require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
echo $tabs->render('privacy');
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
