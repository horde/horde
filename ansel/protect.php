<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$vars = Horde_Variables::getDefaultVariables();
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($vars->get('gallery'));
} catch (Ansel_Exception $e) {
    $notification->push($e->getMessage());
    Horde::url('list.php')->redirect();
    exit;
}
$form = new Horde_Form($vars, _("This gallery is protected by a password. Please enter it below."));
$form->addVariable($gallery->get('name'), 'name', 'description', false);
$form->addVariable($gallery->get('desc'), 'desc', 'description', false);
$form->addVariable(_("Password"), 'passwd', 'password', true);
$form->addHidden('', 'url', 'text', true);
$form->addHidden('', 'gallery', 'int', true);
if ($form->validate()) {
    if ($gallery->get('passwd') != $vars->get('passwd')) {
        $notification->push(_("Incorrect password"), 'horde.warning');
    } else {
        $session->set('ansel', 'passwd/' . $gallery->id, hash('md5', $vars->get('passwd')));
        $url = $vars->get('url');
        if (empty($url)) {
            $url = Horde::url('view.php')->add('gallery', $gallery->id);
        } else {
            $url = Horde::url($url);
        }
        $url->redirect();
        exit;
    }
}

$page_output->header(array(
    'title' => $title
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo '<div class="header">' . Ansel::getBreadCrumbs() . '</div>';
$form->renderActive(null, null, null, 'post');
$page_output->footer();
