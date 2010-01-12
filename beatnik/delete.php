<?php
/**
 * Delete records
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/merk/LICENSE.
 */

define('BEATNIK_BASE', dirname(__FILE__));
require_once BEATNIK_BASE . '/lib/base.php';
require_once BEATNIK_BASE . '/lib/Forms/DeleteRecord.php';

$vars = Horde_Variables::getDefaultVariables();
list($type, $record) = $beatnik_driver->getRecord(Horde_Util::getFormData('id'));

$form = new DeleteRecord($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    if (Horde_Util::getFormData('submitbutton') == _("Delete")) {
        try {
            $result = $beatnik_driver->deleteRecord($info);
        } catch (Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('viewzone.php'), $info));
            exit;
        }
        $notification->push(_("Record deleted"), 'horde.success');
        if ($info['rectype'] == 'soa') {
            header('Location: ' . Horde::applicationUrl('listzones.php'));
        } else {
            header('Location: ' . Horde::applicationUrl('viewzone.php'));
        }
    } else {
        $notification->push(_("Record not deleted"), 'horde.warning');
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('viewzone.php'), $info));
    }
    exit;
} elseif (!$form->isSubmitted() && $record) {
    foreach ($record as $field => $value) {
        $vars->set($field, $value);
    }
}


$title = _("Delete");
require BEATNIK_BASE . '/templates/common-header.inc';
require BEATNIK_BASE . '/templates/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('delete.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
