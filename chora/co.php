<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once __DIR__ . '/lib/Application.php';

/* If a revision is specified, it's safe to cache for a long time. */
if (empty($_GET['r'])) {
    Horde_Registry::appInit('chora');
} else {
    session_cache_expire(10080);
    Horde_Registry::appInit('chora', array('session_cache_limiter' => 'public'));
}

/* If we know we're at a directory, just go to browsedir.php. */
if ($atdir) {
    require CHORA_BASE . '/browsedir.php';
    exit;
}

/* Should we pretty-print this output or not? */
$plain = Horde_Util::getFormData('p', 0);

/* Create the VC_File object and populate it. */
try {
    $file = $VC->getFile($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Get the revision number. */
$r = Horde_Util::getFormData('r');

/* If no revision is specified, default to HEAD. */
if (is_null($r)) {
    $r = $file->getRevision();
}

/* Is this a valid revision being requested? */
if (!$VC->isValidRevision($r)) {
    Chora::fatal(sprintf(_("Revision Not Found: %s is not a valid revision"), $r), '400 Bad Request');
}

/* Retrieve the actual checkout. */
try {
    $checkOut = $VC->checkout($file->getPath(), $r);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Get the MIME type of the file, or at least our best guess at it. */
$mime_type = Horde_Mime_Magic::filenameToMIME($fullname);
if ($mime_type == 'application/octet-stream') {
    $mime_type = 'text/plain';
}

if (!$plain) {
    /* Pretty-print the checked out copy */
    $pretty = Chora::pretty($mime_type, $checkOut);

    if (strpos($mime_type, 'text/plain') !== false) {
        $data = $pretty->render('inline');
        $data = reset($data);
        $rendered = '<div class="fixed">' . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($data['data'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) . '</div>';
    } elseif (strpos($mime_type, 'image/') !== false) {
        $rendered = Horde::img(Horde::selfUrl(true)->add('p', 1), '', '', '');
    } elseif ($pretty->canRender('inline')) {
        $data = $pretty->render('inline');
        $data = reset($data);
        $rendered = $data['data'];
    } else {
        $rendered = Horde::link(Horde::selfUrl(true)->add('p', 1)) . Horde::img('download.png') . ' ' . sprintf(_("Download revision %s"), $r) . '</a>';
    }

    /* Get this revision's attributes in printable form. */
    $log = $file->getLog($r);

    $title = sprintf(_("Revision %s (%s ago) for:"),
                     $r,
                     Chora::readableTime($log->getDate(), true));

    $page_output->addScriptFile('stripe.js', 'horde');
    Chora::header($title);
    echo Chora::getFileViews($where, $r)->render('co');
    require CHORA_TEMPLATES . '/checkout/checkout.inc';
    $page_output->footer();
    exit;
}

/* Download the file. */

// Get data.
$content = '';
while ($line = fgets($checkOut)) {
    $content .= $line;
}
fclose($checkOut);

// Get name.
$filename = $file->getFileName();
if ($browser->getBrowser() == 'opera') {
    $filename = strtr($filename, ' ', '_');
}

// Send headers.
$browser->downloadHeaders($filename, $mime_type, false, strlen($content));

// Send data.
echo $content;
