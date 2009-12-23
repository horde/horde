<?php
/**
 * Handle Horde_Ajax_Imple:: requests.
 *
 * Mandatory components:
 * 'imple'
 *
 * Optional components:
 * 'impleApp'
 * 'sessionWrite'
 * 'post' - name of POST variable that contains any values required to be sent
 *          by POST. Format is the same as imple (/var1=value/var2=value)
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
 */

/* Load core first because we need access to Horde_Util::. */
require_once dirname(__FILE__) . '/../lib/core.php';

if (!($path = Horde_Util::getFormData('imple'))) {
    exit;
}

$path = explode('/', ltrim($path, '/'));
$impleName = array_shift($path);

$args = array();
$post = array();

foreach ($path as $pair) {
    if (strpos($pair, '=') === false) {
        $args[$pair] = true;
    } else {
        list($name, $val) = explode('=', $pair);
        $args[$name] = $val;
    }
}

/* See if any variables required a POST */
if (!empty($args['post'])) {
    $posts = explode('/', Horde_Util::getPost($args['post']));
    unset($args['post']);

    /* Populate from POST */
    foreach ($posts as $pair) {
        if (strpos($pair, '=') === false) {
            $post[$pair] = true;
        } else {
            list($name, $val) = explode('=', $pair);
            $post[$name] = $val;
        }
    }
}

/* Determine if we can get away with a readonly session */
if (empty($args['sessionWrite'])) {
    $horde_session_control = 'readonly';
}
$horde_no_logintasks = true;
require_once dirname(__FILE__) . '/../lib/base.php';

if (isset($args['impleApp'])) {
    $registry = Horde_Registry::singleton();
    $registry->pushApp($args['impleApp']);
    $imple = Horde_Ajax_Imple::factory(array($args['impleApp'], $impleName));
} else {
    $imple = Horde_Ajax_Imple::factory($impleName);
}

$result = $imple->handle($args, $post);

$ct = empty($_SERVER['Content-Type'])
    ? (is_string($result) ? 'plain' : 'json')
    : $_SERVER['Content-Type'];

Horde::sendHTTPResponse($result, $ct);
