<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

$vars = Horde_Variables::getDefaultVariables();
if (!$vars->exists('id') && $vars->exists('timer')) {
    $timer_id = $vars->get('timer');
    $timers = @unserialize($prefs->getValue('running_timers'));
    if ($timers && isset($timers[$timer_id])) {
        $tname = $timers[$timer_id]['name'];
        $tformat = $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a';
        $vars->set('hours', round((float)(time() - $timer_id) / 3600, 2));
        if ($prefs->getValue('add_description')) {
            $vars->set('note', sprintf(_("Using the \"%s\" stop watch from %s to %s"), $tname, date($tformat, $timer_id), date($tformat, time())));
        }
        $notification->push(sprintf(_("The stop watch \"%s\" has been stopped."), $tname), 'horde.success');
        unset($timers[$timer_id]);
        $prefs->setValue('running_timers', serialize($timers));
    }
}

switch ($vars->get('formname')) {
case 'hermes_form_time_entry':
    $form = new Hermes_Form_Time_Entry($vars);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        try {
            if ($vars->exists('id')) {
                $msg = _("Your time was successfully updated.");
                $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array($info));
                $do_redirect = true;
            } else {
                $msg = _("Your time was successfully entered.");
                $GLOBALS['injector']->getInstance('Hermes_Driver')->enterTime($GLOBALS['registry']->getAuth(), $info);
                $do_redirect = false;
            }
        } catch (Exception $e) {
            Horde::logMessage($e, 'err');
            $notification->push(sprintf(_("There was an error storing your timesheet: %s"), $e->getMessage()), 'horde.error');
            $do_redirect = false;
        }

        $notification->push($msg, 'horde.success');
        if ($do_redirect) {
            $url = $vars->get('url');
            if (empty($url)) {
                $url = Horde::url('time.php');
            }
            header('Location: ' . $url);
            exit;
        }
    }
    break;

default:
    if ($vars->exists('id')) {
        // We are updating a specific entry, load it into the form variables.
        $id = $vars->get('id');
        if (!Hermes::canEditTimeslice($id)) {
            $notification->push(_("Access denied; user cannot modify this timeslice."), 'horde.error');
            Horde::url('time.php')->redirect();
        }
        $myhours = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $id));
        if (is_array($myhours)) {
            foreach ($myhours as $item) {
                if (isset($item['id']) && $item['id'] == $id) {
                    foreach ($item as $key => $value) {
                        $vars->set($key, $value);
                    }
                }
            }
        }
    }
    $form = new Hermes_Form_Time_Entry($vars);
    break;
}
$form->setCostObjects($vars);

$title = $vars->exists('id') ? _("Edit Time") : _("New Time");
require $registry->get('templates', 'horde') . '/common-header.inc';
require HERMES_TEMPLATES . '/menu.inc';
$form->renderActive(new Horde_Form_Renderer(), $vars, 'entry.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
