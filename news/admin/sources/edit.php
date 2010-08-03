<?php
/**
 * The News script to edit sources.
 *
 * Copyright 2007 - 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: edit.php 183 2008-01-06 17:39:50Z duck $
 */

define('NEWS_BASE', dirname(__FILE__) . '/../..');
require_once NEWS_BASE . '/lib/base.php';
require '../tabs.php';

$vars = Horde_Variables::getDefaultVariables();
$source_id = $vars->get('source_id');
$title = !empty($source_id) ? _("Edit Source") : _("Add Source");

$form = new Horde_Form($vars, $title, 'editsource');
if ($source_id && !$form->isSubmitted()) {
    $sources = $news->getSources(true);
    foreach ($sources[$source_id] as $key => $val) {
        if ($key != 'source_image') {
            $vars->set($key, $val);
        }
    }
}

$form->addHidden('', 'source_id', 'int', $source_id);
$form->addVariable(_("Name"), 'source_name', 'text', true);
$form->addVariable(_("Url"), 'source_url', 'text', true);

$v = &$form->addVariable(_("Resize Image"), 'source_image_resize', 'boolean', false);
$v->setDefault(true);
$form->addVariable(_("Image"), 'source_image', 'image', false);

if ($form->validate()) {
    $form->getInfo(null, $info);
    $result = $news->saveSource($info);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage() . ': ' . $result->getDebugInfo(), 'horde.error');
    } else {
        $notification->push(_("Source saved succesfully."));
        Horde::applicationUrl('admin/sources/index.php')->redirect();
    }
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';
echo $tabs->render('sources');
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
