<?php
/**
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('wicked');

if (!($text = Horde_Util::getFormData('page_text'))) {
    exit;
}

$view = $injector->createInstance('Horde_View');
$page = new Wicked_Page();
$view->text = $page->getProcessor()->transform($text);

Wicked::setTopbar();
$page_output->header(array(
    'title' => sprintf(_("Edit %s"), Horde_Util::getFormData('age'))
));
$notification->notify(array('listeners' => 'status'));
echo $view->render('edit/preview');
$page_output->footer();
