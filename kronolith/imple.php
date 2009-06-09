<?php
/**
 * $Horde: kronolith/imple.php,v 1.5 2009/01/06 18:00:59 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$path = Horde_Util::getFormData('imple');
if (!$path) {
    exit;
}
if ($path[0] == '/') {
    $path = substr($path, 1);
}
$path = explode('/', $path);
$impleName = array_shift($path);

$imple = Kronolith_Imple::factory($impleName);
if (!$imple) {
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

if (!empty($_SERVER['Content-Type'])) {
    $ct = $_SERVER['Content-Type'];
} else {
    $ct = is_string($result) ? 'plain' : 'json';
}

switch ($ct) {
case 'json':
    header('Content-Type: application/json');
    echo Horde_Serialize::serialize($result, Horde_Serialize::JSON, NLS::getCharset());
    break;

case 'plain':
    header('Content-Type: text/plain');
    echo $result;
    break;

case 'html':
    header('Content-Type: text/html');
    echo $result;
    break;

default:
    echo $result;
}
