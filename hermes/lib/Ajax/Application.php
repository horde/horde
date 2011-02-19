<?php
/**
 * Defines the AJAX interface for Hermes.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Hermes
 */
class Hermes_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = true;

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
     * @return the new timeslice
     */
    public function enterTime()
    {
        $slice = new Hermes_Slice();
        $slice->readForm();
        $employee = $GLOBALS['registry']->getAuth();
        try {
            $id = $GLOBALS['injector']->getInstance('Hermes_Driver')->enterTime($employee, $slice);
            $new = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours(array('id' => $id));
            return current($new)->toJson();

        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
    }

    /**
     * Get a list of client deliverables.
     *
     *
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
            return $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array($sid));
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
        return current($new)->toJson();
    }

    public function poll()
    {
        // keepalive
    }


}
