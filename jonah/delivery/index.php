<?php
/**
 * $Horde: jonah/delivery/index.php,v 1.27 2009/06/10 05:24:47 slusarz Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

$parts = explode('/', Horde_Util::getPathInfo());
$lastpart = null;
$deliverytype = null;
$criteria = array();
foreach ($parts as $part) {
    if (empty($part)) {
        // Double slash in the URL path.  Ignore this empty part.
        continue;
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
                              __FILE__, __LINE__, PEAR_LOG_WARNING);
            exit;
        }
        break;
    }
}

if (empty($deliveryType)) {
    $deliveryType = 'html';
}

include dirname(__FILE__) . '/' . basename($deliveryType) . '.php';
