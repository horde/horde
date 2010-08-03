<?php
/**
 * The News script to delete a source.
 *
 * Copyright 2007 - 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: delete.php 183 2008-01-06 17:39:50Z duck $
 */

define('NEWS_BASE', dirname(__FILE__) . '/../..');
require_once NEWS_BASE . '/lib/base.php';
require '../tabs.php';

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Do you really wont to delete this source?"), 'delete');
$form->setButtons(array(_("Remove"), _("Cancel")));

$source_id = Horde_Util::getFormData('source_id');
$form->addHidden('', 'source_id', 'int', $source_id);

if ($form->validate()) {
    if (Horde_Util::getFormData('submitbutton') == _("Remove")) {
        $news->deleteSource($source_id);
        if ($result instanceof PEAR_Error) {
            $notification->push(_("Source was not deleted.") . ' ' . $result->getMessage(), 'horde.error');
        } else {
            $notification->push(_("Source deleted."), 'horde.success');
        }
    } else {
        $notification->push(_("Source was not deleted."), 'horde.warning');
    }
    Horde::applicationUrl('admin/sources/index.php')->redirect();
}

require NEWS_BASE . '/templates/common-header.inc';
require NEWS_BASE . '/templates/menu.inc';
echo $tabs->render('sources');
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
