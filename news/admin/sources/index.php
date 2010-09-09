<?php
/**
 * The News script to navigate source settings.
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: index.php 183 2008-01-06 17:39:50Z duck $
 */

define('NEWS_BASE', dirname(__FILE__) . '/../..');
require_once NEWS_BASE . '/lib/base.php';
require '../tabs.php';

$title = _("Sources Administration");
$sources = $news->getSources(true);
if ($sources instanceof PEAR_Error) {
    $notification->push($sources->getDebugInfo(), 'horde.error');
    $sources = array();
}

$edit_url = Horde::url('admin/sources/edit.php');
$edit_img = Horde::img('edit.png', _("Edit"));
$delete_url = Horde::url('admin/sources/delete.php');
$delete_img = Horde::img('delete.png', _("Delete"));
$view_url = Horde::url('browse.php');
$view_img = Horde::img('category.png', _("View items"));

foreach ($sources as $source_id => $source) {
    $sources[$source_id]['actions'][] = Horde::link(Horde_Util::addParameter($view_url, 'source_id', $source_id), _("View articles")) .
                                        $view_img . '</a>';
    $sources[$source_id]['actions'][] = Horde::link(Horde_Util::addParameter($delete_url, 'source_id', $source_id), _("Delete")) .
                                        $delete_img . '</a>';
    $sources[$source_id]['actions'][] = Horde::link(Horde_Util::addParameter($edit_url, 'source_id', $source_id), _("Edit")) .
                                        $edit_img . '</a>';
}

$view = new News_View();
$view->sources = $sources;
$view->add_url = Horde::link($edit_url, _("Add New")) . _("Add New") . '</a>';

Horde::addScriptFile('tables.js', 'horde');
require NEWS_BASE . '/templates/common-header.inc';
require NEWS_BASE . '/templates/menu.inc';
echo $tabs->render('sources');
echo $view->render('/sources/index.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
