#!/usr/bin/env php
<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hermes = Horde_Registry::appInit('hermes');

printf(_("Deleting data that was exported/billed more than %s days ago.\n"), $conf['time']['days_to_keep']);
$GLOBALS['injector']->getInstance('Hermes_Driver')->purge();
