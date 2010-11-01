<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

// Will redirect to login page if not authenticated.
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

// Load initial page as defined by view mode & preferences.
require IMP_Auth::getInitialPage();
