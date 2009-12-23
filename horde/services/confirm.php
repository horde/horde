<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

$horde_no_logintasks = true;
require_once dirname(__FILE__) . '/../lib/base.php';

$identity = Horde_Prefs_Identity::singleton();
list($message, $type) = $identity->confirmIdentity(Horde_Util::getFormData('h'));
$notification->push($message, $type);

$url = Horde_Util::addParameter(Horde::url('services/prefs.php', true), array('app' => 'horde', 'group' => 'identities'), null, false);
header('Location: ' . $url);
