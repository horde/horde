<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

// As of right now, imples don't need read/write session access.
$session_control = 'readonly';
$session_timeout = 'none';

$authentication = 'horde';
require_once dirname(__FILE__) . '/lib/base.php';

$viewmode = $_SESSION['imp']['view'];

if (!($path = Util::getFormData('imple'))) {
    if ($viewmode == 'dimp') {
        Horde::sendHTTPResponse(new stdClass(), 'json');
    }
    exit;
}

if ($path[0] == '/') {
    $path = substr($path, 1);
}

$path = explode('/', $path);
$impleName = reset($path);

if (!($imple = IMP_Imple::factory($impleName))) {
    if ($viewmode == 'dimp') {
        Horde::sendHTTPResponse(new stdClass(), 'json');
    }
    exit;
}

$args = array();
foreach ($path as $pair) {
    if (strpos($pair, '=') === false) {
        $args[$pair] = true;
    } else {
        list($name, $val) = explode('=', $pair);
        $args[$name] = $val;
    }
}

$result = $imple->handle($args);

$ct = empty($_SERVER['Content-Type'])
    ? (is_string($result) ? 'plain' : 'json')
    : $_SERVER['Content-Type'];

Horde::sendHTTPResponse($result, $ct);
