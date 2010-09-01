<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('CRUMB_BASE', dirname(__FILE__));
require_once CRUMB_BASE . '/lib/base.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once CRUMB_BASE . '/lib/Forms/AddClient.php';

$vars = Horde_Variables::getDefaultVariables();
$formname = $vars->get('formname');

$addform = new Horde_Form_AddClient($vars);
if (is_a($addform, 'PEAR_Error')) {
    Horde::logMessage($addform, 'ERR');
    $notification->push(_("An internal error has occurred.  Details have been logged for the administrator."));
    $addform = null;
}

if ($addform->validate($vars)) {
print_r($addform->getInfo());
}

$url = Horde::url('addclient.php');
$title = _("Add New Client");

require CRUMB_TEMPLATES . '/common-header.inc';
require CRUMB_TEMPLATES . '/menu.inc';

if (!empty($addform)) {
    $addform->renderActive(null, null, $url, 'post');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
