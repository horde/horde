<?php
/**
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

// As of right now, imples don't need read/write session access.
$session_control = 'readonly';
$session_timeout = 'none';

@define('IMP_BASE', dirname(__FILE__));
$authentication = 'horde';
require_once IMP_BASE . '/lib/base.php';

if ($_SESSION['imp']['view'] == 'dimp') {
    $path_info = Util::getPathInfo();
    if (empty($path_info)) {
        IMP::sendHTTPResponse(new stdClass(), 'json');
    }

    if ($path_info[0] == '/') {
        $path_info = substr($path_info, 1);
    }
    $path = explode('/', $path_info);
    $impleName = array_shift($path);

    if (!($imple = IMP_Imple::factory($impleName))) {
        IMP::sendHTTPResponse(new stdClass(), 'json');
    }
} else {
    if (!($path = Util::getFormData('imple'))) {
        exit;
    }
    if ($path[0] == '/') {
        $path = substr($path, 1);
    }
    $path = explode('/', $path);
    $impleName = array_shift($path);

    if (!($imple = IMP_Imple::factory($impleName))) {
        exit;
    }
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

IMP::sendHTTPResponse($result, $ct);
