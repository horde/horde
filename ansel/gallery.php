<?php
/**
 * Copyright 2001-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

require_once __DIR__ . '/lib/Application.php';

$params = array();
$actionID = Horde_Util::getFormData('actionID');
if ($actionID == 'downloadzip') {
    $params['nocompress'] = true;
}
Horde_Registry::appInit('ansel', $params);

// Redirect to the gallery list if no action has been requested.
if (is_null($actionID)) {
    Horde::url('view.php?view=List', true)->redirect();
    exit;
}
if (!Ansel_ActionHandler::galleryActions($actionID)) {
    Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
    exit;
}

