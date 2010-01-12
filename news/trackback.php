<?php
/**
 * $Id: trackback.php 803 2008-08-27 08:29:20Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

$news_authentication = 'none';
require_once dirname(__FILE__) . '/lib/base.php';

if ($browser->isRobot()) {
    exit;
}

header('Content-type: text/xml');

/* Try to create object */
$trackback_data = array(
    'id'        => Horde_Util::getFormData('id'),
    'host'      => $_SERVER['REMOTE_ADDR'],
    'title'     => Horde_Util::getFormData('title'),
    'excerpt'   => Horde_Util::getFormData('excerpt'),
    'url'       => Horde_Util::getFormData('url'),
    'blog_name' => Horde_Util::getFormData('blog_name')
);

$trackback = News::loadTrackback($trackback_data);
if ($trackback instanceof PEAR_Error) {
    echo Services_Trackback::getResponseError($trackback->getMessage(), 1);
    exit;
}

/* Check if the needed data is posted */
foreach ($trackback_data as $key => $value) {
    if ($key != 'excerpt' && empty($value)) {
        echo Services_Trackback::getResponseError($key . ' is required', 2);
        exit;
    }
}

/* Get the response and check if the request has all data */
$request = $trackback->receive();
if ($trackback instanceof PEAR_Error) {
    echo Services_Trackback::getResponseError($request->getMessage(), 3);
    exit;
}

/* Check if the message is a spam */
if (!empty($conf['trackback']['spamcheck'])) {
    foreach ($conf['trackback']['spamcheck'] as $type) {
        $trackback->createSpamCheck($type);
    }
    if ($trackback->checkSpam()) {
        echo Services_Trackback::getResponseError('SPAM', 4);
        exit;
    }
}

/* Check if the forum exists */
$id = (int)$trackback->get('id');
if ($id == 0) {
    echo Services_Trackback::getResponseError(sprintf(_("Blog entry %s does not exist."), $id), 5);
}

$news_data = $news->get($id);
if ($news_data instanceof PEAR_Error) {
    echo Services_Trackback::getResponseError($news_data->getMessage(), 5);
    exit;
}

/* Store the trackback data */
$result = $news->saveTrackback($id,
                               $trackback->get('title'),
                               $trackback->get('url'),
                               $trackback->get('excerpt'),
                               $trackback->get('blog_name'),
                               $trackback->get('trackback_url'));
if ($result instanceof PEAR_Error) {
    echo Services_Trackback::getResponseError($result->getMessage(), 6);
    exit;
}

/* All done */
echo Services_Trackback::getResponseSuccess();
