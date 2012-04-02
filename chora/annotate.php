<?php
/**
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('chora');

/* Spawn the file object. */
try {
    $fl = $VC->getFile($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Retrieve the desired revision from the GET variable. */
$rev = Horde_Util::getFormData('rev');
if (!$rev || !$VC->isValidRevision($rev)) {
    Chora::fatal(sprintf(_("Revision %s not found"), $rev), '404 Not Found');
}

switch (Horde_Util::getFormData('actionID')) {
case 'log':
    $log = $fl->getLog($rev);
    if (!is_null($log)) {
        echo '<em>' . _("Author") . ':</em> ' . Chora::showAuthorName($log->getAuthor(), true) . '<br />' .
            '<em>' . _("Date") . ':</em> ' . Chora::formatDate($log->getDate()) . '<br /><br />' .
            Chora::formatLogMessage($log->getMessage());
    }
    exit;
}

try {
    $lines = $VC->annotate($fl, $rev);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = sprintf(_("Source Annotation of %s (revision %s)"), $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)), $rev);
$extraLink = sprintf('<a href="%s">%s</a> | <a href="%s">%s</a>',
                     Chora::url('co', $where, array('r' => $rev)), _("View"),
                     Chora::url('co', $where, array('r' => $rev, 'p' => 1)), _("Download"));

$page_output->addScriptFile('annotate.js');
$page_output->addInlineJsVars(array(
    'var Chora' => array(
        'ANNOTATE_URL' => (string)Horde::url('annotate.php', true)->add(array('actionID' => 'log', 'f' => $where, 'rev' => '')),
        'loading_text' => _("Loading...")
    )
));

$page_output->header(array(
    'title' => $title
));
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/annotate/header.inc';

$author = '';
$style = 0;

while (list(,$line) = each($lines)) {
    $lineno = $line['lineno'];
    $author = Chora::showAuthorName($line['author']);
    $prevRev = $rev;
    $rev = $line['rev'];
    if ($prevRev != $rev) {
        $style = (++$style % 2);
    }
    $prev = $fl->getPreviousRevision($rev);

    $line = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($line['line'], 'space2html', array('encode' => true, 'encode_all' => true));
    include CHORA_TEMPLATES . '/annotate/line.inc';
}

require CHORA_TEMPLATES . '/annotate/footer.inc';
$page_output->footer();
