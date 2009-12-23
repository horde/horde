<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

$horde_no_logintasks = true;
require_once dirname(__FILE__) . '/../lib/base.php';

$cid = Horde_Util::getFormData('cid');
if (empty($cid)) {
    exit;
}

$cache = Horde_Cache::singleton($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));
$cdata = @unserialize($cache->get($cid, $conf['cache']['default_lifetime']));
if (!$cdata) {
    exit;
}

$browser->downloadHeaders('cacheObject', $cdata['ctype'], true, strlen($cdata['data']));
echo $cdata['data'];
