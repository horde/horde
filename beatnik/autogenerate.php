<?php
/**
 * Delete records
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

require_once BEATNIK_BASE . '/lib/Forms/Autogenerate.php';

$viewurl = Horde::url('viewzone.php');

$vars = Horde_Variables::getDefaultVariables();
$form = new Autogenerate($vars);

if ($form->validate($vars)) {
    if (Horde_Util::getFormData('submitbutton') == _("Autogenerate")) {
        try {
            $result = Beatnik::autogenerate($vars);
        } catch (Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            Horde::url('listzones.php')->redirect();
        }
    } else {
        $notification->push(_("Autogeneration not performed"), 'horde.warning');
    }

    $viewurl->redirect();
}

$page_output->header(array(
    'title' => _("Autogenerate")
));
require BEATNIK_BASE . '/templates/menu.inc';
$form->renderActive(null, null, Horde::url('autogenerate.php'), 'post');
$page_output->footer();
