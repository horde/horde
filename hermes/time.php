<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hermes = Horde_Registry::appInit('hermes');

require_once HERMES_BASE . '/lib/Forms/Time.php';
require_once HERMES_BASE . '/lib/Table.php';

$vars = Horde_Variables::getDefaultVariables();

$delete = $vars->get('delete');
if (!empty($delete)) {
    $result = $hermes->driver->updateTime(array(array('id' => $delete, 'delete' => true)));
    if (is_a($result, 'PEAR_Error')) {
        Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        $notification->push(sprintf(_("There was an error deleting the time: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(_("The time entry was successfully deleted."), 'horde.success');
        $vars->remove('delete');
    }
}

switch ($vars->get('formname')) {
case 'submittimeform':
    $time = array();
    $item = $vars->get('item');
    if (is_null($item) || !count($item)) {
        $notification->push(_("No timeslices were selected to submit."),
                            'horde.error');
    } else {
        foreach ($item as $id => $val) {
            $time[] = array('id' => $id);
        }
        $result = $hermes->driver->markAs('submitted', $time);
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error submitting your time: %s"), $result->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Your time was successfully submitted."),
                                'horde.success');
            $vars = new Horde_Variables();
        }
    }
    break;
}

// We are displaying all time.
$tabs = Hermes::tabs();
$criteria = array('employee' => $GLOBALS['registry']->getAuth(),
                  'submitted' => false,
                  'link_page' => 'time.php');
$table = new Horde_Core_Ui_Table('week', $vars,
                            array('title' => _("My Unsubmitted Time"),
                                  'name' => 'hermes/hours',
                                  'params' => $criteria));

$template = new Horde_Template();
$template->setOption('gettext', true);
$template->set('postUrl', Horde::url('time.php', false, -1));
$template->set('sessionId', Horde_Util::formInput());
$template->set('table', $table->render());

$title = _("My Time");
$print_view = (Horde_Util::getFormData('print') == 'true');
if (!$print_view) {
    Horde::addScriptFile('popup.js', 'horde', true);
}
require HERMES_TEMPLATES . '/common-header.inc';

if ($print_view) {
    require $registry->get('templates', 'horde') . '/javascript/print.js';
} else {
    $print_link = Horde_Util::addParameter(Horde::url('time.php'), 'print', 'true');
    require HERMES_TEMPLATES . '/menu.inc';
}

echo $tabs;
echo $template->fetch(HERMES_TEMPLATES . '/time/form.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
