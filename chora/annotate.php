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

/* Spawn the file object. */
$fl = $VC->getFileObject($where, $cache);
Chora::checkError($fl);
$rev_ob = $VC->getRevisionObject();

/* Retrieve the desired revision from the GET variable. */
$rev = Util::getFormData('rev', '1.1');
if (!$VC->isValidRevision($rev)) {
    Chora::fatal(sprintf(_("Revision %s not found"), $rev), '404 Not Found');
}

switch (Util::getFormData('actionID')) {
case 'log':
    if (isset($fl->logs[$rev])) {
        $log = $fl->logs[$rev];
        $out = '<em>' . _("Author") . ':</em> ' . Chora::showAuthorName($log->queryAuthor(), true) . '<br />' .
            '<em>' . _("Date") . ':</em> ' . Chora::formatDate($log->queryDate()) . '<br /><br />' .
            Chora::formatLogMessage($log->queryLog());
    } else {
        $out = '';
    }
    echo $out;
    exit;
}

$ann = &$VC->getAnnotateObject($fl);
Chora::checkError($lines = $ann->doAnnotate($rev));

$title = sprintf(_("Source Annotation of %s (revision %s)"), Text::htmlAllSpaces($where), $rev);
$extraLink = sprintf('<a href="%s">%s</a> | <a href="%s">%s</a>',
                     Chora::url('co', $where, array('r' => $rev)), _("View"),
                     Chora::url('co', $where, array('r' => $rev, 'p' => 1)), _("Download"));

Horde::addScriptFile('prototype.js', 'chora', true);
Horde::addScriptFile('annotate.js', 'chora', true);

$js_vars = array(
    'ANNOTATE_URL' => Util::addParameter(Horde::applicationUrl('annotate.php'), array('actionID' => 'log', 'f' => $where, 'rev' => ''), null, false),
    'loading_text' => _("Loading...")
);

require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/annotate/header.inc';

$author = '';
$style = 0;

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

    $prev_key = array_search($rev, $fl->revs);
    $prev = isset($fl->revs[$prev_key + 1])
            ? $fl->revs[$prev_key + 1]
            : null;

    $line = Text::htmlAllSpaces($line['line']);
    include CHORA_TEMPLATES . '/annotate/line.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
