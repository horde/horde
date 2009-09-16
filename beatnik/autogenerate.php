<?php
/**
 * Delete records
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/merk/LICENSE.
 *
 * $Horde: beatnik/autogenerate.php,v 1.16 2009/07/14 00:25:28 mrubinsk Exp $
 *
 * @author Duck <duck@obala.net>
 */

define('BEATNIK_BASE', dirname(__FILE__));
require_once BEATNIK_BASE . '/lib/base.php';
require_once BEATNIK_BASE . '/lib/Forms/Autogenerate.php';

$viewurl = Horde::applicationUrl('viewzone.php');

$vars = Horde_Variables::getDefaultVariables();
$form = new Autogenerate($vars);

if ($form->validate($vars)) {
    if (Horde_Util::getFormData('submitbutton') == _("Autogenerate")) {
       $result = Beatnik::autogenerate($vars);
       if (is_a($result, 'PEAR_Error')) {
           $notification->push($zonedata, 'horde.error');
           header('Location:' . Horde::applicationUrl('listzones.php'));
           exit;
       }
    } else {
        $notification->push(_("Autogeneration not performed"), 'horde.warning');
    }

    header('Location: ' . $viewurl);
    exit;
}

$title = _("Autogenerate");
require BEATNIK_BASE . '/templates/common-header.inc';
require BEATNIK_BASE . '/templates/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('autogenerate.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
