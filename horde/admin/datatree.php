<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

function addTree($parent, $parent_id, $indent = 1)
{
    global $datatree, $tree;

    $nodes = $datatree->getById(DATATREE_FORMAT_FLAT, $parent_id, true, $parent, 1);
    $expanded = $tree->isExpanded($parent);
    $url = Horde::url('datatree.php');
    foreach ($nodes as $id => $node) {
        if ($id == $parent_id) {
            continue;
        }
        $tree->addNode($parent . ':' . $id, $parent, $datatree->getShortName($node), $indent, false, array('url' => Horde_Util::addParameter($url, 'show', $datatree->getParam('group') . ':' . $id) . '#show'));
        if ($expanded) {
            addTree($parent . ':' . $id, $id, $indent + 1);
        }
    }
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

require_once 'Horde/DataTree.php';
$tree = $injector->getInstance('Horde_Tree')->getTree('datatree', 'Javascript');
$tree->setOption('alternate', true);

$driver = $conf['datatree']['driver'];
$config = Horde::getDriverConfig('datatree', $conf['datatree']['driver']);
$datatree = DataTree::singleton($conf['datatree']['driver']);
$roots = $datatree->getGroups();

if (is_a($roots, 'PEAR_Error')) {
    $notification->push($roots);
} else {
    foreach ($roots as $root) {
        $tree->addNode($root, null, $root, 0, false);
        $datatree = DataTree::singleton($driver, array_merge($config, array('group' => $root)));
        addTree($root, DATATREE_ROOT);
    }
}

if ($show = Horde_Util::getFormData('show')) {
    list($root, $id) = explode(':', $show);
    $datatree = DataTree::singleton($driver, array_merge($config, array('group' => $root)));
    $data = $datatree->getData($id);
    $attributes = $datatree->getAttributes($id);
}

$title = _("DataTree Browser");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo '<h1 class="header">' . Horde::img('datatree.png') . ' ' . _("DataTree") . '</h1>';
$tree->renderTree();
if ($show) {
    echo '<br /><div class="text" style="white-space:pre"><a id="show"></a>';
    echo "<strong>Data:</strong>\n";
    ob_start('htmlspecialchars');
    print_r($data);
    ob_end_flush();
    echo "\n<strong>Attributes:</strong>\n";
    ob_start('htmlspecialchars');
    print_r($attributes);
    ob_end_flush();
    echo '</div>';
}
require HORDE_TEMPLATES . '/common-footer.inc';
