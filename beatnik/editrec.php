<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

require_once BEATNIK_BASE . '/lib/Forms/EditRecord.php';

$vars = Horde_Variables::getDefaultVariables();
$url = Horde::url('editrec.php');
list($type, $record) = $beatnik->driver->getRecord(Horde_Util::getFormData('id'));

$form = new EditRecord($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    try {
        $result = $beatnik->driver->saveRecord($info);
    } catch (Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }

    $notification->push('Record data saved.', 'horde.success');

    // Check to see if this is a new domain
    $edit = $vars->get('id');
    if ($info['rectype'] == 'soa' && !$edit) {
        // if added a soa redirect to the autogeneration page
        $url = Horde::url('autogenerate.php')->add(array('rectype' => 'soa', 'curdomain' => $info['zonename']));
    } else {
        $url = Horde::url('viewzone.php');
    }

    $url->redirect();

} elseif (!$form->isSubmitted() && $record) {
    foreach ($record as $field => $value) {
        $vars->set($field, $value);
    }
}

$title = $form->getTitle();
Beatnik::notifyCommits();
require $registry->get('templates', 'horde') . '/common-header.inc';
require BEATNIK_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, $url, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
