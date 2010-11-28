<?php

$view = $injector->createInstance('Horde_View');
$view->addHelper('Chora_Diff_Helper');

echo $view->renderPartial('app/views/logMessage', array('collection' => array($patchset['log'])));

echo '<ul class="commit-filelist">';
$i = 0;
foreach ($patchset['members'] as $member) {
    $i++;

    switch ($member['status']) {
    case Horde_Vcs_Patchset::ADDED:
        $class = 'commit-file-added';
        $alt = '+';
        break;

    case Horde_Vcs_Patchset::DELETED:
        $class = 'commit-file-deleted';
        $alt = '-';
        break;

    default:
        $class = 'commit-file-modified';
        $alt = '*';
    }

    echo '<li><span class="' . $class . '">' . $alt . '</span><a href="#diff-' . $i . '">'
        . htmlspecialchars($member['file']) . '</a>' . (isset($member['added']) ? (' (<span class="diffadd">+' . $member['added'] . '</span>, <span class="diffdel">-' . $member['deleted'] . '</span>) ') : '') . '</li>';
}
echo '</ul>';

$segmentStart = microtime(true);
$i = 0;
foreach ($patchset['members'] as $member) {
    $i++;
    echo $view->diff($VC->getFileObject($member['file']), $member['from'], $member['to'], "diff-$i");

    // Flush every .1 seconds to keep the page rendering.
    $now = microtime(true);
    if ($now - $segmentStart > .1) {
        ob_flush();
        flush();
        $segmentStart = $now;
    }
}

echo $view->diffCaption();
