<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$identity = $injector->getInstance('Horde_Core_Factory_Identity')->create()->confirmIdentity(Horde_Util::getFormData('h'));

Horde::getServiceLink('prefs')->add('group', 'identities')->redirect();
