<?php
/**
 * $Horde: beatnik/editrec.php,v 1.35 2009/07/03 10:05:29 duck Exp $
 *
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('BEATNIK_BASE', dirname(__FILE__));
require_once BEATNIK_BASE . '/lib/base.php';
require_once BEATNIK_BASE . '/lib/Forms/EditRecord.php';

$vars = Horde_Variables::getDefaultVariables();
$url = Horde::applicationUrl('editrec.php');
list($type, $record) = $beatnik_driver->getRecord(Horde_Util::getFormData('id'));

$form = new EditRecord($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $result = $beatnik_driver->saveRecord($info);

    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage() . ': ' . $result->getDebugInfo(), 'horde.error');
    } else {
        $notification->push('Record data saved.', 'horde.success');

        // Check to see if this is a new domain
        $edit = $vars->get('id');
        if ($info['rectype'] == 'soa' && !$edit) {
            // if added a soa redirect to the autogeneration page
            $url = Horde_Util::addParameter(Horde::applicationUrl('autogenerate.php'),
                                      array('rectype' => 'soa', 'curdomain' => $info['zonename']), false, false);
        } else {
            $url = Horde::applicationUrl('viewzone.php');
        }
    }

    header('Location: ' . $url);
    exit;

} elseif (!$form->isSubmitted() && $record) {
    foreach ($record as $field => $value) {
        $vars->set($field, $value);
    }
}

$title = $form->getTitle();
require BEATNIK_TEMPLATES . '/common-header.inc';
require BEATNIK_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, $url, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
