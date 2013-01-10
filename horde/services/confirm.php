<?php
/**
 * Identity confirmation script.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$identity = $injector->getInstance('Horde_Core_Factory_Identity')->create()->confirmIdentity(Horde_Util::getFormData('h'));

$registry->getServiceLink('prefs')->add('group', 'identities')->redirect();
