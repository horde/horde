<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
new Horde_Application(array('nologintasks' => true));

$identity = Horde_Prefs_Identity::singleton();
list($message, $type) = $identity->confirmIdentity(Horde_Util::getFormData('h'));
$notification->push($message, $type);

$url = Horde_Util::addParameter(Horde::url('services/prefs.php', true), array('app' => 'horde', 'group' => 'identities'), null, false);
header('Location: ' . $url);
