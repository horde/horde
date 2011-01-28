<?php
/**
 * Cached data output script.
 *
 * MANDATORY: type (css, js, or app)
 * OPTIONAL: cid (required for type == [css, js]), nocache
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
 */

/* The amount of time (in minutes) to cache the generated CSS and JS files.
 * DEFAULT: 525600 = 1 year */
$expire_time = 525600;

require_once dirname(__FILE__) . '/../lib/Application.php';

if (!($path = Horde_Util::getFormData('cache'))) {
    exit;
}

$path = explode('/', ltrim($path, '/'));
$type = array_shift($path);

$args = array();
foreach ($path as $pair) {
    list($name, $val) = explode('=', $pair);
    $args[$name] = $val;
}

if (empty($args['nocache'])) {
    $session_cache_limiter = 'public';
    session_cache_expire($expire_time);
} else {
    $session_cache_limiter = 'nocache';
}

Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'session_cache_limiter' => $session_cache_limiter,
    'session_control' => 'readonly'
));

switch ($type) {
case 'app':
    if (empty($args['app'])) {
        exit;
    }
    try {
        $result = $registry->callAppMethod($args['app'], 'cacheOutput', array('args' => array($args)));
        $data = $result['data'];
        $type = $result['type'];
    } catch (Horde_Exception $e) {
        exit;
    }
    break;

case 'css':
case 'js':
    if (empty($args['cid'])) {
        exit;
    }

    try {
        $cache = $injector->getInstance('Horde_Cache');
    } catch (Horde_Exception $e) {
        exit;
    }

    $data = $cache->get($args['cid'], 0);
    $type = 'text/' . $type;
    break;

default:
    exit;
}

// If cache info doesn't exist, just output an empty body.
header('Content-Type: ' . $type);
echo $data;
