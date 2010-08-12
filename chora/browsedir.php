<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('chora');

if (!$atdir) {
    require CHORA_BASE . '/browsefile.php';
    exit;
}

$onb = $VC->hasFeature('snapshots')
    ? Horde_Util::getFormData('onb')
    : null;

try {
    $atticFlags = (bool)$acts['sa'];
    $dir = $VC->getDirObject($where, array('quicklog' => true, 'rev' => $onb, 'showattic' => $atticFlags));
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
    ? Horde::widget(Chora::url('browsedir', $where . '/', array('onb' => $onb, 'sa' => ($acts['sa'] ? 0 : 1))), $acts['sa'] ? _("Hide Deleted Files") : _("Show Deleted Files"), 'widget', '', '', $acts['sa'] ? _("Hide _Deleted Files") : _("Show _Deleted Files"))
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
    $url[$key] = Chora::url('browsedir', $where . '/', $arg, array('onb' => $onb));
}

$branches = array();
if ($VC->hasFeature('branches')) {
    $branches = $dir->getBranches();
}

/* Print out the directory header. */
$printAllCols = count($fileList);
$sortdirclass = $acts['sbt'] ? 'sortdown' : 'sortup';

Horde::addScriptFile('tables.js', 'horde');
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/directory/header.inc';

/* Unless we're at the top, display the 'back' bar. */
if ($where != '') {
    $url = Chora::url('browsedir', preg_replace('|[^/]+$|', '', $where), array('onb' => $onb));
    require CHORA_TEMPLATES . '/directory/back.inc';
}

/* Display all the directories first. */
if ($dirList) {
    echo '<tbody>';
    foreach ($dirList as $currentDir) {
        if ($conf['hide_restricted'] && Chora::isRestricted($currentDir)) {
            continue;
        }
        $url = Chora::url('browsedir', $where . '/' . $currentDir . '/', array('onb' => $onb));
        $currDir = $injector->getInstance('Horde_Text_Filter')->filter($currentDir, 'space2html', array('encode' => true, 'encode_all' => true));
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

        $icon = $injector->getInstance('Horde_Mime_Viewer')->getIcon($mimeType);

        $author = Chora::showAuthorName($lg->queryAuthor());
        $filerev = $lg->queryRevision();
        $date = $lg->queryDate();
        $log = $lg->queryLog();
        $attic = $currFile->isDeleted();
        $fileName = $where . ($attic ? '/' . 'Attic' : '') . '/' . $realname;
        $name = $injector->getInstance('Horde_Text_Filter')->filter($realname, 'space2html', array('encode' => true, 'encode_all' => true));
        $url = Chora::url('browsefile', $fileName, array('onb' => $onb));
        $readableDate = Chora::readableTime($date);
        if ($log) {
            $shortLog = Horde_String::truncate(str_replace("\n", ' ', trim($log)), $conf['options']['shortLogLength']);
        }
        require CHORA_TEMPLATES . '/directory/file.inc';
    }
    echo '</tbody>';
}

echo '</table>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
