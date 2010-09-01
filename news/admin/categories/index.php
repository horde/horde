<?php
/**
 * The News script to navigate categories.
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
require NEWS_BASE . '/admin/tabs.php';

// Get category list
$categories = $news_cat->getCategories(false);
if ($categories instanceof PEAR_Error) {
    $notification->push($categories->getMessage(), 'horde.error');
    $categories = array();
}

/* Set up the template fields. */
$title = _("Category Administration");
$edit_url = Horde::url('admin/categories/edit.php');
$edit_img = Horde::img('edit.png', _("Edit"));
$delete_url = Horde::url('admin/categories/delete.php');
$delete_img = Horde::img('delete.png', _("Delete"));

foreach ($categories as $category_id => $category) {
    $categories[$category_id]['actions'][] = Horde::link(Horde_Util::addParameter($delete_url, 'category_id', $category_id), _("Delete")) .
                                    $delete_img . '</a>';
    $categories[$category_id]['actions'][] = Horde::link(Horde_Util::addParameter($edit_url, 'category_id', $category_id), _("Edit")) .
                                     $edit_img . '</a>';
}

$view = new News_View();
$view->categories = $categories;
$view->add_url = Horde::link($edit_url, _("Add New")) . _("Add New") . '</a>';

Horde::addScriptFile('tables.js', 'horde');
require NEWS_BASE . '/templates/common-header.inc';
require NEWS_BASE . '/templates/menu.inc';
echo $tabs->render('cetegories');
echo $view->render('/categories/index.php');
require $registry->get('templates', 'horde') . '/common-footer.inc';
