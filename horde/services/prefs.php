<?php
/**
 * Preferences UI page.
 *
 * URL Parameters
 * --------------
 *   - actionID: (string) Action ID.
 *   - app: (string) The current Horde application.
 *   - group: (string) The current preferences group.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde');

$prefs_ui = new Horde_Core_Prefs_Ui($injector->getInstance('Horde_Variables'));

/* Handle form submission. */
$prefs_ui->handleForm();

/* Generate the UI. */
$prefs_ui->generateUI();
