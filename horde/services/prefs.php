<?php
/**
 * Preferences UI page.
 *
 * URL Parameters
 * --------------
 * 'actionID' - (string) Action ID.
 * 'app' - (string) The current Horde application.
 * 'group' - (string) The current preferences group.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde');

$prefs_ui = new Horde_Core_Prefs_Ui(Horde_Variables::getDefaultVariables());

/* Handle form submission. */
$prefs_ui->handleForm();

/* Generate the UI. */
$prefs_ui->generateUI();
