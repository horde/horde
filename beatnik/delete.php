<?php
/**
 * Delete records
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl
 */

require_once __DIR__ . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

require_once BEATNIK_BASE . '/lib/Forms/DeleteRecord.php';

$vars = Horde_Variables::getDefaultVariables();
list($type, $record) = $beatnik->driver->getRecord(Horde_Util::getFormData('id'));

$form = new DeleteRecord($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    if (Horde_Util::getFormData('submitbutton') == _("Delete")) {
        try {
            $result = $beatnik->driver->deleteRecord($info);
        } catch (Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            Horde::url('viewzone.php')->add($info)->redirect();
        }
        $notification->push(_("Record deleted"), 'horde.success');
        if ($info['rectype'] == 'soa') {
            Horde::url('listzones.php')->redirect();
        } else {
            Horde::url('viewzone.php')->redirect();
        }
    } else {
        $notification->push(_("Record not deleted"), 'horde.warning');
        Horde::url('viewzone.php')->add($info)->redirect();
    }
} elseif (!$form->isSubmitted() && $record) {
    foreach ($record as $field => $value) {
        $vars->set($field, $value);
    }
}

$page_output->header(array(
    'title' => _("Delete")
));
require BEATNIK_BASE . '/templates/menu.inc';
$form->renderActive(null, null, Horde::url('delete.php'), 'post');
$page_output->footer();
