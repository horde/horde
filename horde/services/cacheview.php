<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$cid = Horde_Util::getFormData('cid');
if (empty($cid)) {
    exit;
}

$cache = $injector->getInstance('Horde_Cache');
$cdata = @unserialize($cache->get($cid, $conf['cache']['default_lifetime']));
if (!$cdata) {
    exit;
}

$browser->downloadHeaders('cacheObject', $cdata['ctype'], true, strlen($cdata['data']));
echo $cdata['data'];
