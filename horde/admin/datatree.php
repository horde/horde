<?php
/**
 * Horde_DataTree browser.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 */

function _addTree($parent, $parent_id, $datatree, $tree, $indent = 1)
{
    $nodes = $datatree->getById(DATATREE_FORMAT_FLAT, $parent_id, true, $parent, 1);
    $url = Horde::url('admin/datatree.php');

    foreach ($nodes as $id => $node) {
        if ($id != $parent_id) {
            $node_url = $url->copy()->add('show', $datatree->getParam('group') . ':' . $id)->setAnchor('show');

            $tree->addNode($parent . ':' . $id, $parent, $datatree->getShortName($node), $indent, false, array('url' => strval($node_url)));
            _addTree($parent . ':' . $id, $id, $datatree, $tree, $indent + 1);
        }
    }
}

require_once dirname(__FILE__) . '/../lib/Application.php';
$permission = 'datatree';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() && 
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:'.$permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

$tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('datatree', 'Javascript', array(
    'alternate' => true
));

$driver = $conf['datatree']['driver'];
$config = Horde::getDriverConfig('datatree', $conf['datatree']['driver']);
$datatree = Horde_DataTree::singleton($conf['datatree']['driver']);
$roots = $datatree->getGroups();

if ($roots instanceof PEAR_Error) {
    $notification->push($roots);
} else {
    foreach ($roots as $root) {
        $tree->addNode($root, null, $root, 0, false);
        _addTree($root, DATATREE_ROOT, Horde_DataTree::singleton($driver, array_merge($config, array('group' => $root))), $tree);
    }
}

if ($show = Horde_Util::getFormData('show')) {
    list($root, $id) = explode(':', $show);
    $datatree = Horde_DataTree::singleton($driver, array_merge($config, array('group' => $root)));
    $data = $datatree->getData($id);
    $attributes = $datatree->getAttributes($id);
}

$title = _("DataTree Browser");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo '<h1 class="header">' . Horde::img('datatree.png') . ' ' . _("DataTree") . '</h1>';
$tree->renderTree();
if ($show) {
    echo '<br /><div class="text" style="white-space:pre"><a id="show"></a>' .
        "<strong>Data:</strong>\n" .
        htmlspecialchars(print_r($data, true)) .
        "\n<strong>Attributes:</strong>\n" .
        htmlspecialchars(print_r($attributes, true)) .
        '</div>';
}
require HORDE_TEMPLATES . '/common-footer.inc';
