<?php
/**
 * Defines the AJAX interface for Hermes.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Hermes
 */
class Hermes_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Fetch a collection of time slices. For now, just allows a search for
     * all of a single employees time. Either submitted or not submitted.
     *
     * @return array
     */
    public function getTimeSlices()
    {
        $params = array('employee' => $this->_vars->e,
                        'submitted' => $this->_vars->s);

        try {
            $slices = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($params, array(), $this->_vars->sort, $this->_vars->dir);
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return array();
        }
        $json = array();
        foreach ($slices as $slice) {
            $json[] = $slice->toJson();
        }

        return $json;
    }

    /**
     * Enter a time slice
     *
     * @return @array  The new timeslice
     */
    public function enterTime()
    {
        $slice = new Hermes_Slice();
        $slice->readForm();
        $employee = $GLOBALS['registry']->getAuth();
        try {
            $id = $GLOBALS['injector']->getInstance('Hermes_Driver')->enterTime($employee, $slice);
            $new = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $id));
            $GLOBALS['notification']->push(_("Your time was successfully entered."), 'horde.success');

            return current($new)->toJson();
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Get a list of client deliverables.
     *
     * @return array
     */
    public function listDeliverables()
    {
        $client = $this->_vars->c;
        return Hermes::getCostObjectType($client);
        return array_values($GLOBALS['injector']
            ->getInstance('Hermes_Driver')
            ->listDeliverables(array('client_id' => $client)));
    }

    /**
     * Remove a slice
     */
    public function deleteSlice()
    {
        $sid = array('id' => $this->_vars->id, 'delete' => true);
        try {
            $result = $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array($sid));
            $GLOBALS['notification']->push(_("Your time entry was successfully deleted."), 'horde.success');
            return $result;
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Update a slice
     */
    public function updateSlice()
    {
        $slice = new Hermes_Slice();
        $slice->readForm();
        try {
            $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array($slice));
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
        $new = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $slice['id']));
        $GLOBALS['notification']->push(_("Your time was successfully updated."), 'horde.success');
        return current($new)->toJson();
    }

    /**
     * Mark slices as submitted.
     *
     */
    public function submitSlices()
    {
        $time = array();
        $items = explode(':', $this->_vars->items);
        foreach ($items as $id) {
            $time[] = array('id' => $id);
        }
        try {
            $GLOBALS['injector']->getInstance('Hermes_Driver')->markAs('submitted', $time);
        } catch (Horde_Exception $e) {
            $notification->push(sprintf(_("There was an error submitting your time: %s"), $e->getMessage()), 'horde.error');
        }
        $GLOBALS['notification']->push(_("Your time was successfully submitted."), 'horde.success');

        return true;
    }

    /**
     * Add a new timer
     *
     */
    public function addTimer()
    {
        $id = Hermes::newTimer($this->_vars->desc);
        return array('id' => $id);
    }

    /**
     * Stop a timer
     */
    public function stopTimer()
    {
        global $prefs;

        if (!$timer = Hermes::getTimer($this->_vars->t)) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }
        $results = array();
        $tname = $timer['name'];
        $elapsed = ((!$timer['paused']) ? time() - $timer['time'] : 0 )+ $timer['elapsed'];

        $tformat = $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a';
        $results['h'] = round((float)$elapsed / 3600, 2);
        if ($prefs->getValue('add_description')) {
            $results['n'] = sprintf(_("Using the \"%s\" stop watch from %s to %s"), $tname, date($tformat, $timer_id), date($tformat, time()));
        } else {
            $results['n'] = '';
        }
        $GLOBALS['notification']->push(sprintf(_("The stop watch \"%s\" has been stopped."), $tname), 'horde.success');
        Hermes::clearTimer($this->_vars->t);

        return $results;
    }

    public function pauseTimer()
    {
        global $prefs;

        if (!$timer = Hermes::getTimer($this->_vars->t)) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }

        $timer['paused'] = true;
        $timer['elapsed'] += time() - $timer['time'];
        $timer['time'] = 0;
        Hermes::updateTimer($this->_vars->t, $timer);

        return true;
    }

    public function startTimer()
    {
        global $prefs;

        if (!$timer = Hermes::getTimer($this->_vars->t)) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }
        $timer['paused'] = false;
        $timer['time'] = time();
        Hermes::updateTimer($this->_vars->t, $timer);

        return true;
    }

    public function listTimers($running_only = false)
    {
        $timers = $GLOBALS['prefs']->getValue('running_timers');
        if (!empty($timers)) {
            $timers = @unserialize($timers);
        } else {
            $timers = array();
        }
        $return = array();
        foreach ($timers as $id => &$timer) {
            if ($running_only && $timer['paused']) {
                continue;
            }
            $elapsed = ((!$timer['paused']) ? time() - $timer['time'] : 0 )+ $timer['elapsed'];
            $timer['e'] = round((float)$elapsed / 3600, 2);
            $timer['id'] = $id;
            $return[] = $timer;
        }

        return $return;
    }

    public function poll()
    {
        // Return any elapsed time for timers
        return $this->listTimers(true);
    }


}
