<?php
/**
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */
$parts = explode('/', Horde_Util::getPathInfo());
$lastpart = null;
$deliveryType = null;
$criteria = array();
foreach ($parts as $part) {
    if (empty($part)) {
        // Double slash in the URL path.  Ignore this empty part.
        continue;
    }

    // Check for REST-style content type
    if (strpos($part, '.') !== false) {
        $deliveryType = substr($part, strrpos($part, '.') + 1);
        $part = substr($part, 0, strrpos($part, '.'));
    }

    switch($part) {
    case 'html':
    case 'rss':
        $deliveryType = $part;
        break;

    case 'type':
        // Feed type is specially mangled
        $lastpart = 'feed_type';
        break;

    case 'format':
        // Format is specially mangled
        $lastpart = 'channel_format';
        break;

    case 'author':
    case 'channel_format':
    case 'tag':
    case 'tag_id':
    case 'story':
    case 'story_id':
    case 'channel':
    case 'channel_id':
        $lastpart = $part;
        break;

    default:
        if (!empty($lastpart)) {
            $criteria[$lastpart] = $part;
            $lastpart = null;
        } else {
            // An unknown directive
            Horde::logMessage("Malformed request URL: " . Horde_Util::getPathInfo(),
                              'WARN');
            exit;
        }
        break;
    }
}

if (empty($deliveryType)) {
    $deliveryType = 'html';
}

include dirname(__FILE__) . '/' . basename($deliveryType) . '.php';
