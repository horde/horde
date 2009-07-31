<?php
/**
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once 'Horde/Form.php';

$vars = Horde_Variables::getDefaultVariables();
$gallery = $ansel_storage->getGallery($vars->get('gallery'));
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push($gallery->getMessage());
    header('Location: ' . Horde::applicationUrl('list.php'));
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
        $_SESSION['ansel']['passwd'][$gallery->id] = md5($vars->get('passwd'));
        $url = $vars->get('url');
        if (empty($url)) {
            $url = Horde::applicationUrl(Horde_Util::addParameter('view.php', 'gallery', $gallery->id));
        }
        header('Location: ' . $url);
        exit;
    }
}
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
echo '<div class="header">' . Ansel::getBreadCrumbs() . '</div>';
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
