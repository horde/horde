<?php
/**
 * Download service script.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

$vars = $injector->getInstance('Horde_Variables');

if (!isset($vars->app)) {
    exit;
}

$vars->filename = substr($vars->fn, 1);
unset($vars->fn);

$res = $registry->callAppMethod($vars->app, 'download', array(
    'args' => array($vars)
));

if (!isset($res['data'])) {
    exit;
}

if (!array_key_exists('size', $res)) {
    if (is_resource($res['data'])) {
        fseek($res['data'], 0, SEEK_END);
        $res['size'] = ftell($res['data']);
    } else {
        $res['size'] = strlen($res['data']);
    }
}

$browser->downloadHeaders(
    isset($res['name']) ? $res['name'] : $vars->filename,
    isset($res['type']) ? $res['type'] : null,
    false,
    $res['size']
);

if (is_resource($res['data'])) {
    rewind($res['data']);
    while (!feof($res['data'])) {
        echo fread($res['data'], 8192);
    }
    fclose($res['data']);
} else {
    echo $res['data'];
}
