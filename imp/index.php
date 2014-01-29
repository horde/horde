<?php
/**
 * Base redirection page for IMP.
 *
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

// Will redirect to login page if not authenticated.
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

IMP_Auth::getInitialPage()->url->redirect();
