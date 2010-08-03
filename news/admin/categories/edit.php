<?php
/**
 * The News script to edit categories.
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: edit.php 238 2008-01-16 15:28:51Z duck $
 */

define('NEWS_BASE', dirname(__FILE__) . '/../..');
require_once NEWS_BASE . '/lib/base.php';
require NEWS_BASE . '/admin/tabs.php';

$category_id = Horde_Util::getFormData('category_id');
$title = !empty($category_id) ? _("Edit category") : _("Add category");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title, 'editcategory');

if ($category_id && !$form->isSubmitted()) {
    $category = $news_cat->getCatArray($category_id);
    $vars->merge($category);
}

$form->addHidden('', 'category_id', 'int', $category_id);

foreach ($GLOBALS['conf']['attributes']['languages'] as $lang) {
    $flag = (count($conf['attributes']['languages']) > 1) ? News::getFlag($lang) . ' ' : '';
    $form->addVariable($flag . _("Name"), 'category_name_' . $lang, 'text', true);
    $form->addVariable($flag . _("Description"), 'category_description_' . $lang, 'text', false);
}

$form->addVariable(_("Parent"), 'category_parentid', 'radio', false, false, null, array($news_cat->getCategories(), true));
$v = &$form->addVariable(_("Resize Image"), 'image_resize', 'boolean', false);
$v->setDefault(true);
$form->addVariable(_("Image"), 'image', 'image', false);

if ($form->validate()) {
    $form->getInfo(null, $info);
    $result = $news_cat->saveCategory($info);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage() . ': ' . $result->getDebugInfo(), 'horde.error');
    } else {
        $notification->push(sprintf(_("Category succesfully saved.")));
        Horde::applicationUrl('admin/categories/index.php', true)->redirect();
    }
}

require NEWS_BASE . '/templates/common-header.inc';
require NEWS_BASE . '/templates/menu.inc';
echo $tabs->render('cetegories');
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
