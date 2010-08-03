<?php
/**
 * Delete records
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/merk/LICENSE.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

require_once BEATNIK_BASE . '/lib/Forms/Autogenerate.php';

$viewurl = Horde::applicationUrl('viewzone.php');

$vars = Horde_Variables::getDefaultVariables();
$form = new Autogenerate($vars);

if ($form->validate($vars)) {
    if (Horde_Util::getFormData('submitbutton') == _("Autogenerate")) {
        try {
            $result = Beatnik::autogenerate($vars);
        } catch (Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            Horde::applicationUrl('listzones.php')->redirect();
        }
    } else {
        $notification->push(_("Autogeneration not performed"), 'horde.warning');
    }

    $viewurl->redirect();
}

$title = _("Autogenerate");
require BEATNIK_BASE . '/templates/common-header.inc';
require BEATNIK_BASE . '/templates/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('autogenerate.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
