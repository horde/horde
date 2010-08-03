<?php
/**
 * Report offensive content
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/lib/base.php';

$user = Horde_Util::getFormData('user');
if (empty($user)) {
    $notification->push(_("User is not selected"), 'horde.warning');
    Folks::getUrlFor('list', 'list')->redirect();
}

$title = _("Do you really want to report this user?");

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->setButtons(array(_("Report"), _("Cancel")));

$enum = array('advertisement' => _("Advertisement content"),
              'terms' => _("Terms and conditions infringement"),
              'offensive' => _("Offensive content"),
              'copyright' => _("Copyright infringement"));

$form->addVariable($user, 'name', 'description', false);

$form->addHidden('', 'user', 'text', true, true);

$form->addVariable(_("Report type"), 'type', 'radio', true, false, null, array($enum));
$form->addVariable(_("Report reason"), 'reason', 'longtext', true);

$user_id = Horde_Util::getFormData('id');

if ($form->validate()) {
    if (Horde_Util::getFormData('submitbutton') == _("Report")) {

        $body =  _("User") . ': ' . $user . "\n"
            . _("Report type") . ': ' . $enum[$vars->get('type')] . "\n"
            . _("Report reason") . ': ' . $vars->get('reason') . "\n"
            . Folks::getUrlFor('user', $user);

        require FOLKS_BASE . '/lib/Notification.php';
        $rn = new Folks_Notification();
        $result = $rn->notifyAdmins($title, $body);
        if ($result instanceof PEAR_Error) {
            $notification->push(_("User was not reported.") . ' ' .
                                $result->getMessage(), 'horde.error');
        } else {
            $notification->push(_("User was reported."), 'horde.success');
        }
    } else {
        $notification->push(_("User was not reported."), 'horde.warning');
    }
    Folks::getUrlFor('user', $user)->redirect();
}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
