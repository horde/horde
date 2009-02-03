<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';

if (!$atdir && !$VC->isFile($fullname)) {
    Chora::fatal(sprintf(_("$fullname: no such file or directory"), $where), '404 Not Found');
}

/* Scripts needed for both dirs and files. */
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);

if ($atdir) {
    $rev = $VC->hasFeature('snapshots')
        ? Util::getFormData('rev')
        : null;

    try {
        $atticFlags = (bool)$acts['sa'];
        $dir = $VC->getDirObject($where, array('quicklog' => true, 'rev' => $rev, 'showattic' => $atticFlags));
        $dir->applySort($acts['sbt'], $acts['ord']);
        $dirList = $dir->queryDirList();
        $fileList = $dir->queryFileList($atticFlags);
    } catch (Horde_Vcs_Exception $e) {
        Chora::fatal($e);
    }

    /* Decide what title to display. */
    $title = ($where == '')
        ? $conf['options']['introTitle']
        : sprintf(_("Source Directory of /%s"), $where);

    $extraLink = $VC->hasFeature('deleted')
        ? Horde::widget(Chora::url('browse', $where . '/', array('sa' => ($acts['sa'] ? 0 : 1))), $acts['sa'] ? _("Hide Deleted Files") : _("Show Deleted Files"), 'widget', '', '', $acts['sa'] ? _("Hide _Deleted Files") : _("Show _Deleted Files"))
        : '';

    $umap = array(
        'age' => Horde_Vcs::SORT_AGE,
        'rev' => Horde_Vcs::SORT_REV,
        'name' => Horde_Vcs::SORT_NAME,
        'author' => Horde_Vcs::SORT_AUTHOR
    );

    foreach ($umap as $key => $val) {
        $arg = array('sbt' => $val);
        if ($acts['sbt'] == $val) {
            $arg['ord'] = !$acts['ord'];
        }
        $url[$key] = Chora::url('browse', $where . '/', $arg);
    }

    /* Print out the directory header. */
    $printAllCols = count($fileList);

    require CHORA_TEMPLATES . '/common-header.inc';
    require CHORA_TEMPLATES . '/menu.inc';
    require CHORA_TEMPLATES . '/headerbar.inc';
    require CHORA_TEMPLATES . '/directory/header.inc';

    /* Unless we're at the top, display the 'back' bar. */
    if ($where != '') {
        $url = Chora::url('browse', preg_replace('|[^/]+$|', '', $where));
        require CHORA_TEMPLATES . '/directory/back.inc';
    }

    /* Display all the directories first. */
    if ($dirList) {
        echo '<tbody>';
        foreach ($dirList as $currentDir) {
            if ($conf['hide_restricted'] && Chora::isRestricted($currentDir)) {
                continue;
            }
            $url = Chora::url('browse', $where . '/' . $currentDir . '/');
            $currDir = Text::htmlAllSpaces($currentDir);
            require CHORA_TEMPLATES . '/directory/dir.inc';
        }
        echo '</tbody>';
    }

    /* Display all of the files in this directory */
    if ($fileList) {
        echo '<tbody>';
        foreach ($fileList as $currFile) {
            if ($conf['hide_restricted'] &&
                Chora::isRestricted($currFile->queryName())) {
                continue;
            }
            $lg = $currFile->queryLastLog();
            $realname = $currFile->queryName();
            $mimeType = Horde_Mime_Magic::filenameToMIME($realname);

            $icon = Horde_Mime_Viewer::getIcon($mimeType);

            $author = Chora::showAuthorName($lg->queryAuthor());
            $head = $currFile->queryHead();
            $date = $lg->queryDate();
            $log = $lg->queryLog();
            $attic = $currFile->isDeleted();
            $fileName = $where . ($attic ? '/' . 'Attic' : '') . '/' . $realname;
            $name = Text::htmlAllSpaces($realname);
            $url = Chora::url('browse', $fileName);
            $readableDate = Chora::readableTime($date);
            if ($log) {
                $shortLog = str_replace("\n", ' ', trim(substr($log, 0, $conf['options']['shortLogLength'] - 1)));
                if (strlen($log) > 80) {
                    $shortLog .= '...';
                }
            }
            require CHORA_TEMPLATES . '/directory/file.inc';
        }
        echo '</tbody>';
    }

    echo '</table>';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Showing a file. */
$onb = Util::getFormData('onb');
try {
    $fl = $VC->getFileObject($where, array('branch' => $onb));
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = sprintf(_("Revisions for %s"), $where);

$extraLink = Chora::getFileViews($where, 'browse');
$logs = $fl->queryLogs();
$first = end($logs);
$diffValueLeft = $first->queryRevision();
$diffValueRight = $fl->queryRevision();

$sel = '';
foreach ($fl->querySymbolicRevisions() as $sm => $rv) {
    $sel .= '<option value="' . $rv . '">' . $sm . '</option>';
}

$selAllBranches = '';
if ($VC->hasFeature('branches')) {
    foreach (array_keys($fl->queryBranches()) as $sym) {
        $selAllBranches .= '<option value="' . $sym . '"' . (($sym === $onb) ? ' selected="selected"' : '' ) . '>' . $sym . '</option>';
    }
}

Horde::addScriptFile('QuickFinder.js', 'horde', true);
Horde::addScriptFile('revlog.js', 'chora', true);
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/log/header.inc';

$i = 0;
reset($logs);
while (list(,$lg) = each($logs)) {
    $rev = $lg->queryRevision();
    $branch_info = $lg->queryBranch();

    $textUrl = Chora::url('co', $where, array('r' => $rev));
    $commitDate = Chora::formatDate($lg->queryDate());
    $readableDate = Chora::readableTime($lg->queryDate(), true);

    $author = Chora::showAuthorName($lg->queryAuthor(), true);
    $tags = Chora::getTags($lg, $where);

    if ($prevRevision = $fl->queryPreviousRevision($lg->queryRevision())) {
        $diffUrl = Chora::url('diff', $where, array('r1' => $prevRevision, 'r2' => $rev));
    } else {
        $diffUrl = '';
    }

    $logMessage = Chora::formatLogMessage($lg->queryLog());

    require CHORA_TEMPLATES . '/log/rev.inc';

    if (($i++ > 100) && !Util::getFormData('all')) {
        break;
    }
}
require CHORA_TEMPLATES . '/log/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
