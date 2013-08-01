<?php
/**
 * A script to redirect to a given URL, used to hide any referrer data being
 * passed to the remote server and potentially exposing any session IDs.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author    Marko Djukic <marko@oblo.com>
 * @category  Horde
 * @copyright 2003-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'session_control' => 'none'
));

if (strlen($url = trim($_GET['url'])) &&
    // IE will process the last ;URL= string, not the first, allowing
    // protocols that shouldn't be let through.
    !preg_match('/;\s*url\s*=/i', $url) &&
    // Check the HMAC
    Horde::verifySignedQueryString($_SERVER['QUERY_STRING'])) {
    // URL verified -> so redirect
    header('Refresh: 0; URL=' . $url);
}
