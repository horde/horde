<?php
/**
 * The Ulaform script to list the available forms.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/forms.php,v 1.32 2009-07-08 18:30:01 slusarz Exp $
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* Only admin should be using this. */
if (!Horde_Auth::isAdmin()) {
    Horde::authenticationFailureRedirect();
}

$forms = $ulaform_driver->getFormsList();
if (is_a($forms, 'PEAR_Error')) {
    $notification->push(sprintf(_("There was an error listing forms: %s."), $forms->getMessage()), 'horde.error');
    $forms = array();
} elseif (empty($forms)) {
    $notification->push(_("No available forms."), 'horde.warning');
}

$images = array('delete' => Horde::img('delete.png', _("Delete Form"), null, $registry->getImageDir('horde')),
                'edit' => Horde::img('edit.png', _("Edit Form"), '', $registry->getImageDir('horde')),
                'preview' => Horde::img('display.png', _("Preview Form")),
                'html' => Horde::img('html.png', _("Generate HTML")));

$template->set('header', _("Available Forms"));
$template->set('listheaders', array(_("Form Name"), _("Action")));
$template->set('forms', $forms, true);
$template->set('images', $images);
$template->set('menu', Ulaform::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

$title = _("Forms List");
require ULAFORM_TEMPLATES . '/common-header.inc';
echo $template->fetch(ULAFORM_TEMPLATES . '/forms/forms.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
