<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$cid = Horde_Util::getFormData('cid');
if (!strlen($cid)) {
    exit;
}

$cdata = @unserialize($injector->getInstance('Horde_Cache')->get($cid, $conf['cache']['default_lifetime']));
if (!$cdata) {
    exit;
}

$browser->downloadHeaders('cacheObject', $cdata['ctype'], true, strlen($cdata['data']));
echo $cdata['data'];
