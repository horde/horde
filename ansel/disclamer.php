<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Zagar <jan.zagar@siol.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$vars = Horde_Variables::getDefaultVariables();
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($vars->get('gallery'));
} catch (Ansel_Exception $e) {
    $notification->push($gallery->getMessage());
    Horde::url('view.php?view=List', true)->redirect();
    exit;
}
$url = $vars->get('url');
$form = new Horde_Form($vars, _("Content Disclaimer"), 'disclamer');
$form->addVariable($gallery->get('name'), 'name', 'description', false);
$form->addVariable($gallery->get('desc'), 'desc', 'description', false);
$form->addHidden('', 'url', 'text', true);
$form->addHidden('', 'gallery', 'int', true);
$msg = sprintf(_("Photo content may be offensive. You must be over %d to continue."), $gallery->get('age'));
$form->addVariable($msg, 'warning', 'description', false);
$form->setButtons(array(sprintf(_("Continue - I'm over %d"), $gallery->get('age')), _("Cancel")));

if ($form->isSubmitted()) {
    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push("You are not authorised to view this photo.", 'horde.warning');
        Horde::url('view.php?view=List', true)->redirect();
        exit;
    } else {
        $session->set('ansel', 'user_age', (int)$gallery->get('age'));
        $url->redirect();
        exit;
    }
}

require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
