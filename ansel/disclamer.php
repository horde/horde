<?php
/**
 * $Horde: ansel/disclamer.php,v 1.6 2009/06/10 00:33:01 mrubinsk Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Zagar <jan.zagar@siol.net>
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once 'Horde/Form.php';

$vars = Horde_Variables::getDefaultVariables();
$gallery = $ansel_storage->getGallery($vars->get('gallery'));
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push($gallery->getMessage());
    header('Location: ' . Horde::applicationUrl('view.php?view=List', true));
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
        header('Location: ' . Horde::applicationUrl('view.php?view=List', true));
        exit;
    } else {
        $_SESSION['ansel']['user_age'] = (int)$gallery->get('age');
        header('Location: ' . $url, true);
        exit;
    }
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
