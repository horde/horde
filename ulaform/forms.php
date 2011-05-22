<?php
/**
 * The Ulaform script to list the available forms.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ulaform', array('admin' => true));

$forms = $ulaform_driver->getFormsList();
if (is_a($forms, 'PEAR_Error')) {
    $notification->push(sprintf(_("There was an error listing forms: %s."), $forms->getMessage()), 'horde.error');
    $forms = array();
} elseif (empty($forms)) {
    $notification->push(_("No available forms."), 'horde.warning');
}

$images = array('delete' => Horde::img('delete.png', _("Delete Form"), null),
                'edit' => Horde::img('edit.png', _("Edit Form"), ''),
                'preview' => Horde::img('display.png', _("Preview Form")),
                'html' => Horde::img('html.png', _("Generate HTML")));

$template = $injector->getInstance('Horde_Template');
$template->set('header', _("Available Forms"));
$template->set('listheaders', array(_("Form Name"), _("Action")));
$template->set('forms', $forms, true);
$template->set('images', $images);

$title = _("Forms List");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $template->fetch(ULAFORM_TEMPLATES . '/forms/forms.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
