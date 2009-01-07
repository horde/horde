<?php
/**
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once 'Horde/Text/Filter.php';

/* Spawn the file object. */
$fl = $VC->getFileObject($where, $cache);
$rev_ob = $VC->getRevisionObject();
Chora::checkError($fl);

/* Retrieve the desired revision from the GET variable. */
$rev = Util::getFormData('rev', '1.1');
if (!$VC->isValidRevision($rev)) {
    Chora::fatal(sprintf(_("Revision %s not found"), $rev), '404 Not Found');
}

$ann = &$VC->getAnnotateObject($fl);
Chora::checkError($lines = $ann->doAnnotate($rev));

$title = sprintf(_("Source Annotation of %s (revision %s)"), Text::htmlAllSpaces($where), $rev);
$extraLink = sprintf('<a href="%s">%s</a> | <a href="%s">%s</a>',
                     Chora::url('co', $where, array('r' => $rev)), _("View"),
                     Chora::url('co', $where, array('r' => $rev, 'p' => 1)), _("Download"));

$author = '';
$i = $style = 0;

/* Map of revisions for finding the previous revision to a change. */
$revMap = $fl->revs;
sort($revMap);
$rrevMap = array_flip($revMap);

require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/annotate/header.inc';

/* Use this counter so that we can give each tooltip object a unique
 * id attribute (which we use to set the tooltip text later). */
while (list(,$line) = each($lines)) {
    $lineno = $line['lineno'];
    $author = Chora::showAuthorName($line['author']);
    $prevRev = $rev;
    $rev = $line['rev'];
    if ($prevRev != $rev) {
        $style = (++$style % 2);
    }
    $prev = (isset($rrevMap[$rev]) && isset($revMap[$rrevMap[$rev] - 1]))
        ? $revMap[$rrevMap[$rev] - 1]
        : null;
    $line = Text::htmlAllSpaces($line['line']);
    include CHORA_TEMPLATES . '/annotate/line.inc';
    ++$i;
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
