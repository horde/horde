<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('chora');

/* Spawn the repository and file objects */
try {
    $fl = $VC->getFileObject($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Initialise the form variables correctly. */
$r1 = Horde_Util::getFormData('r1');
$r2 = Horde_Util::getFormData('r2');

/* Ensure that we have valid revision numbers. */
if (!$VC->isValidRevision($r1) || !$VC->isValidRevision($r2)) {
    Chora::fatal(_("Malformed Query"), '500 Internal Server Error');
}

/* If no type has been specified, then default to human readable. */
$type = Horde_Util::getFormData('t', 'colored');
if (Horde_Util::getFormData('ty') == 'u') {
    $type = 'unified';
}

/* Unless otherwise specified, show whitespace differences and 3 lines
 * of context. */
$ws = Horde_Util::getFormData('ws', 1);
$num = (int)Horde_Util::getFormData('num', 3);

/* Cache the output of the diff for a week - it can be longer, since
 * it should never change. */
header('Cache-Control: max-age=604800');

/* All is ok, proceed with the diff. Always make sure there is a newline at
 * the end of the file - patch requires it. */
if ($type != 'colored') {
    header('Content-Type: text/plain');
    echo implode("\n", $VC->diff($fl, $r1, $r2, array('num' => $num, 'type' => $type, 'ws' => $ws))) . "\n";
    exit;
}

/* Human-Readable diff. */
$abbrev_r1 = $VC->abbrev($r1);
$abbrev_r2 = $VC->abbrev($r2);
$title = sprintf(_("Diff for %s between version %s and %s"),
                 $injector->getInstance('Horde_Text_Filter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)), $abbrev_r1, $abbrev_r2);

/* Format log entries. */
$log_messages = array();
foreach ($VC->getRevisionRange($fl, $r1, $r2) as $val) {
    $clog = $fl->queryLogs($val);
    if (!is_null($clog)) {
        $fileinfo = $clog->queryFiles($where);
        $stats = ($fileinfo && isset($fileinfo['added']))
            ? array('added' => $fileinfo['added'], 'deleted' => $fileinfo['deleted'])
            : array();

        $log_messages[] = array_merge(array(
            'rev' => $val,
            'msg' => Chora::formatLogMessage($clog->queryLog()),
            'author' => Chora::showAuthorName($clog->queryAuthor(), true),
            'branchinfo' => $clog->queryBranch(),
            'date' => Chora::formatDate($clog->queryDate()),
            'tags' => Chora::getTags($clog, $where),
        ), $stats);
    }
}

/* Get list of diff types. */
$diff_types = array_flip($VC->availableDiffTypes());

Horde::addScriptFile('stripe.js', 'horde');
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/diff/hr/header.inc';

$mime_type = Horde_Mime_Magic::filenameToMIME($fullname);
if (substr($mime_type, 0, 6) == 'image/') {
    /* Check for images. */
    $url1 = Chora::url('co', $where, array('r' => $r1, 'p' => 1));
    $url2 = Chora::url('co', $where, array('r' => $r2, 'p' => 1));

    echo "<tr><td><img src=\"$url1\" alt=\"" . htmlspecialchars($r1) . '" /></td>' .
        "<td><img src=\"$url2\" alt=\"" . htmlspecialchars($r2) . '" /></td></tr>';
} else {
    /* Retrieve the tree of changes. */
    $lns = $VC->diff($fl, $r1, $r2, array('human' => true, 'num' => $num, 'ws' => $ws));
    if (!$lns) {
        /* Is the diff empty? */
        require CHORA_TEMPLATES . '/diff/hr/nochange.inc';
    } else {
        /* Iterate through every header block of changes. */
        foreach ($lns as $header) {
            $lefthead = $header['oldline'];
            $righthead = $header['newline'];
            require CHORA_TEMPLATES . '/diff/hr/row.inc';

            /* Each header block consists of a number of changes
             * (add, remove, change). */
            $curContext = '';
            foreach ($header['contents'] as $change) {
                if (!empty($curContext) && $change['type'] != 'empty') {
                    $line = $curContext;
                    $curContext = '';
                    require CHORA_TEMPLATES . '/diff/hr/empty.inc';
                }

                switch ($change['type']) {
                case 'add':
                    $line = '';
                    foreach ($change['lines'] as $l) {
                        $line .= htmlspecialchars($l) . '<br />';
                    }
                    require CHORA_TEMPLATES . '/diff/hr/add.inc';
                    break;

                case 'remove':
                    $line = '';
                    foreach ($change['lines'] as $l) {
                        $line .= htmlspecialchars($l) . '<br />';
                    }
                    require CHORA_TEMPLATES . '/diff/hr/remove.inc';
                    break;

                case 'empty':
                    $curContext .= htmlspecialchars($change['line']) . '<br />';
                    break;

                case 'change':
                    /* Pop the old/new stacks one by one, until both are
                     * empty. */
                    $oldsize = count($change['old']);
                    $newsize = count($change['new']);
                    $left = $right = '';
                    for ($row = 0, $rowMax = max($oldsize, $newsize); $row < $rowMax; ++$row) {
                        $left .= isset($change['old'][$row]) ? htmlspecialchars($change['old'][$row]) : '';
                        $left .= '<br />';
                        $right .= isset($change['new'][$row]) ? htmlspecialchars($change['new'][$row]) : '';
                        $right .= '<br />';
                    }
                    require CHORA_TEMPLATES . '/diff/hr/change.inc';
                    break;
                }
            }

            if (!empty($curContext)) {
                $line = $curContext;
                $curContext = '';
                require CHORA_TEMPLATES . '/diff/hr/empty.inc';
            }
        }
    }
}

require CHORA_TEMPLATES . '/diff/hr/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
