<?php
/**
 * Handle Imple requests (Horde_Core_Ajax_Imple).
 *
 * Mandatory components:
 *   - imple: (string) Imple name.
 *
 * Optional components:
 *   - impleApp: (string) App of Imple.
 *   - sessionWrite: (boolean) ?
 *   - post: (string) Name of POST variable that contains any values required
 *           to be sent by POST. Format is the same as imple
 *           (/var1=value/var2=value)
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once dirname(__FILE__) . '/../lib/Application.php';

if (!($path = Horde_Util::getFormData('imple'))) {
    exit;
}

$path = explode('/', ltrim($path, '/'));
$impleName = array_shift($path);

$args = $post = array();

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

try {
    Horde_Registry::appInit(
        'horde',
        array(
            'nologintasks' => true,
            'session_control' => empty($args['sessionWrite']) ? 'readonly' : null,
            'authentication' => 'throw'
        )
    );
    $impleargs = $impleName;
    if (isset($args['impleApp'])) {
        $registry->pushApp($args['impleApp']);
        $impleargs = array($args['impleApp'], $impleName);
    }
    $imple = $injector->getInstance('Horde_Core_Factory_Imple')->create($impleargs, array(), true);
    $result = $imple->handle($args, $post);
} catch (Horde_Exception $e) {
    Horde::logMessage($e, 'ERR');
    $result = $e->getMessage();
}

$ct = empty($_SERVER['Content-Type'])
    ? (is_string($result) ? 'plain' : 'json')
    : $_SERVER['Content-Type'];

$response = ($result instanceof Horde_Core_Ajax_Response)
    ? $result
    : new Horde_Core_Ajax_Response_Raw($result);
$response->sendAndExit($ct);
