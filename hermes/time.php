<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

$vars = Horde_Variables::getDefaultVariables();
$delete = $vars->get('delete');
if (!empty($delete)) {
    try {
        $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array(array('id' => $delete, 'delete' => true)));
    } catch (Horde_Exception $e) {
        $notification->push(sprintf(_("There was an error deleting the time: %s"), $e->getMessage()), 'horde.error');
    }
    $notification->push(_("The time entry was successfully deleted."), 'horde.success');
    $vars->remove('delete');
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
        try {
            $GLOBALS['injector']->getInstance('Hermes_Driver')->markAs('submitted', $time);
            $notification->push(_("Your time was successfully submitted."), 'horde.success');
            $vars = new Horde_Variables();
        } catch (Horde_Exception $e) {
            $notification->push(sprintf(_("There was an error submitting your time: %s"), $e->getMessage()), 'horde.error');
        }
    }
    break;
}

// We are displaying all time.
$tabs = Hermes::tabs();
$criteria = array('employee' => $GLOBALS['registry']->getAuth(),
                  'submitted' => false,
                  'link_page' => 'time.php');
$table = new Hermes_Table('week', $vars,
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
require $registry->get('templates', 'horde') . '/common-header.inc';

if ($print_view) {
    require $registry->get('templates', 'horde') . '/javascript/print.js';
} else {
    $print_link = Horde_Util::addParameter(Horde::url('time.php'), 'print', 'true');
    require HERMES_TEMPLATES . '/menu.inc';
}

echo $tabs;
echo $template->fetch(HERMES_TEMPLATES . '/time/form.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
