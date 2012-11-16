<?php
/**
 * Defines the AJAX actions used in Hermes.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Hermes
 */
class Hermes_Ajax_Application_Handler extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Fetch a collection of time slices. For now, just allows a search for
     * all of a single employees time. Either submitted or not submitted.
     * Expects the following values in $this->vars:
     *   - e:     The employee id
     *   - s:     Include submitted slices if true
     *   - sort:  The sort-by value
     *   - dir:   The sort direction
     *
     * @return array  An array of time slice data.
     */
    public function getTimeSlices()
    {
        $params = array(
            'employee' => $this->vars->e,
            'submitted' => $this->vars->s);

        $json = array();
        try {
            $slices = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->getHours($params);
            foreach ($slices as $slice) {
                $json[] = $slice->toJson();
            }
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $json;
    }

    /**
     * Enter a new time slice. @see Hermes_Slice::readForm for the data expected
     * to be sent in the posted form.
     *
     * @return array  The new timeslice
     */
    public function enterTime()
    {
        $slice = new Hermes_Slice();
        $slice->readForm();
        $employee = $GLOBALS['registry']->getAuth();
        try {
            $id = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->enterTime($employee, $slice);
            $new = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->getHours(array('id' => $id));
            $GLOBALS['notification']
                ->push(_("Your time was successfully entered."), 'horde.success');

            return current($new)->toJson();
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Get a list of client deliverables. Expects the following in $this->vars:
     *   - c: The client id, or an array of client ids if querying for specific
     *        clients. Returns all deliverables otherwise.
     *
     * @return array @see Hermes::getCostObjectType
     */
    public function listDeliverables()
    {
        $client = !empty($this->vars->c) ? $this->vars->c : null;

        return Hermes::getCostObjectType($client);
    }

    /**
     * Remove a slice. Expects the following in $this->vars:
     *   - id:  The slice id
     *
     * @return boolean
     */
    public function deleteSlice()
    {
        $sid = array('id' => $this->vars->id, 'delete' => true);
        try {
            $result = $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->updateTime(array($sid));
            $GLOBALS['notification']->push(
                _("Your time entry was successfully deleted."), 'horde.success');

            return $result;
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Update a slice. @see Hermes_Slice::readForm() for the data expeted in
     * the posted form.
     *
     * @return array  The new slice data.
     */
    public function updateSlice()
    {
        $slice = new Hermes_Slice();
        $slice->readForm();
        try {
            $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array($slice));
            $new = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $slice['id']));
            $GLOBALS['notification']->push(_("Your time was successfully updated."), 'horde.success');

            return current($new)->toJson();
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Mark slices as submitted.  Expects the following in $this->vars:
     *   - items:  The slice ids to submit.
     *
     * @return boolean
     */
    public function submitSlices()
    {
        $time = array();
        foreach (explode(':', $this->vars->items) as $id) {
            $time[] = array('id' => $id);
        }
        try {
            $GLOBALS['injector']
                ->getInstance('Hermes_Driver')
                ->markAs('submitted', $time);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push(sprintf(_("There was an error submitting your time: %s"), $e->getMessage()), 'horde.error');
            return false;
        }

        $GLOBALS['notification']->push(_("Your time was successfully submitted."), 'horde.success');
        return true;
    }

    /**
     * Add a new timer.  Expects the following in $this->vars:
     *   - desc:  The timer description.
     *
     * @return array  An array with an 'id' key.
     */
    public function addTimer()
    {
        $id = Hermes::newTimer($this->vars->desc);
        return array('id' => $id);
    }

    /**
     * Stop a timer. Expects the following in $this->vars:
     *   - t:  The timer id.
     *
     * @return array  An array describing the current timer state. Contains:
     *  - h: The total number of hours elapsed so far.
     *  - n: A note to apply to the description field of a time slice.
     */
    public function stopTimer()
    {
        global $prefs;

        try {
            $timer = Hermes::getTimer($this->vars->t);
        } catch (Horde_Exception_NotFound $e) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }
        $results = array();
        $tname = $timer['name'];
        $elapsed = ((!$timer['paused']) ? time() - $timer['time'] : 0 ) + $timer['elapsed'];

        $tformat = $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a';
        $results['h'] = round((float)$elapsed / 3600, 2);
        if ($prefs->getValue('add_description')) {
            $results['n'] = sprintf(_("Using the \"%s\" stop watch from %s to %s"), $tname, date($tformat, $timer_id), date($tformat, time()));
        } else {
            $results['n'] = '';
        }
        $GLOBALS['notification']->push(sprintf(_("The stop watch \"%s\" has been stopped."), $tname), 'horde.success');
        Hermes::clearTimer($this->vars->t);

        return $results;
    }

    /**
     * Pause a timer. Expects the following data in $this->vars:
     *   - t: The timer id
     *
     * @return boolean
     */
    public function pauseTimer()
    {
        try {
            $timer = Hermes::getTimer($this->vars->t);
        } catch (Horde_Exception_NotFound $e) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }

        // Avoid pausing the same timer twice.
        if ($timer['paused'] || $timer['time'] == 0) {
            return true;
        }
        $timer['paused'] = true;
        $timer['elapsed'] += time() - $timer['time'];
        $timer['time'] = 0;
        Hermes::updateTimer($this->vars->t, $timer);

        return true;
    }

    /**
     * Restart a paused timer. Expects the following data in $this->vars:
     *   - t:  The timer id.
     *
     * @return boolean
     */
    public function startTimer()
    {
        try {
            $timer = Hermes::getTimer($this->vars->t);
        } catch (Horde_Exception_NotFound $e) {
            $GLOBALS['notification']->push(_("Invalid timer requested"), 'horde.error');
            return false;
        }
        $timer['paused'] = false;
        $timer['time'] = time();
        Hermes::updateTimer($this->vars->t, $timer);

        return true;
    }

    /**
     * Return the current list of timers.
     *
     * @param boolean $running_only  Only return running timers if true.
     *
     * @return array  An array of timer arrays. @see Hermes::listTimers()
     */
    public function listTimers($running_only = false)
    {
        return Hermes::listTimers($running_only);
    }

    public function search()
    {
        $criteria = $this->_readSearchForm();

        $slices = $GLOBALS['injector']
            ->getInstance('Hermes_Driver')
            ->getHours($criteria);
        $json = array();

        foreach ($slices as $slice) {
            $json[] = $slice->toJson();
        }
        return $json;
    }

    /**
     * Poll the server. Currently also returns the list of current timer data
     * so the UI can be updated periodically.
     *
     * @return array  An array of timer arrays. @see self::listTimers()
     */
    public function poll()
    {
        // Return any elapsed time for timers
        return $this->listTimers(true);
    }

    /**
     * Reads the search form submitted and return the search criteria
     *
     */
    protected function _readSearchForm()
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $vars = $this->vars;

        $criteria = array();
        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)
            || $GLOBALS['registry']->isAdmin()) {

            if (!empty($vars->employees[0])) {
                $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
                if (!$auth->hasCapability('list')) {
                    $criteria['employee'] = explode(',', $vars->employees[0]);
                    if (empty($criteria['employee'])) {
                        unset($criteria['employee']);
                    }
                } else {
                    $criteria['employee'] = $vars->employees;
                }
            }
        } else {
            $criteria['employee'] = $GLOBALS['registry']->getAuth();
        }
        if (!empty($vars->client)) {
            $criteria['client'] = $vars->client;
        }
        if (!empty($vars->type)) {
            $criteria['jobtype'] = $vars->type;
        }
        if (!empty($vars->costobject)) {
            $criteria['costobject'] = $vars->costobject;
        }
        if (!empty($vars->after_date)) {
            $dt = new Horde_Date($vars->after_date);
            $criteria['start'] = $dt->timestamp();
        }
        if (!empty($vars->before_date)) {
            $dt = new Horde_Date($vars->before_date);
            $criteria['end'] = $dt->add(86400)->timestamp();
        }

        if ($vars->billable !== '') {
            $criteria['billable'] = $vars->billable;
        }
        if ($vars->submitted !== '') {
            $criteria['submitted'] = $vars->submitted;
        }
        if ($vars->exported !== '') {
            $criteria['exported'] = $vars->exported;
        }
Horde::debug($criteria);
        return $criteria;
    }

}