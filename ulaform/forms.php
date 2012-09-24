<?php
/**
 * The Ulaform script to list the available forms.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ulaform', array('admin' => true));

try {
    $forms = $ulaform_driver->getFormsList();
    if (empty($forms)) {
        $notification->push(_("No available forms."), 'horde.warning');
    }
} catch (Ulaform_Exception $e) {
    $notification->push(sprintf(_("There was an error listing forms: %s."), $e->getMessage()), 'horde.error');
    $forms = array();
}

$images = array('delete' => Horde::img('delete.png', _("Delete Form"), null),
                'edit' => Horde::img('edit.png', _("Edit Form"), ''),
                'preview' => Horde::img('display.png', _("Preview Form")),
                'html' => Horde::img('html.png', _("Generate HTML")));

$view = new Horde_View(array('templatePath' => ULAFORM_TEMPLATES));
$view->header = _("Available Forms");
$view->listheaders = array(_("Form Name"), _("Action"));
$view->forms = $forms;
$view->images = $images;

$page_output->header(array(
    'title' => _("Forms List")
));
$notification->notify(array('listeners' => 'status'));
echo $view->render('forms');
$page_output->footer();
