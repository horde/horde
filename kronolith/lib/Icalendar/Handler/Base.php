<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */

/**
 * Wraps logic responsible for importing iCalendar data, taking into account
 * necessary steps to deal with recurrence series and exceptions.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */
class Kronolith_Icalendar_Handler_Base
{
    /**
     * The iCalendar data.
     *
     * @var Horde_Icalendar
     */
    protected $_iCal;

    /**
     * @var Kronolith_Driver
     */
    protected $_driver;

    /**
     * @var array
     */
    protected $_exceptions = array();

    /**
     *
     * @param Horde_Icalendar  $iCal    The iCalendar data.
     * @param Kronolith_Driver $driver  The Kronolith driver.
     * @param array            $params  Any additional parameters needed for
     *                                  the importer.
     */
    public function __construct(
        Horde_Icalendar $iCal, Kronolith_Driver $driver, $params = array())
    {
        $this->_iCal = $iCal;
        $this->_driver = $driver;
        $this->_params = $params;
    }

    /**
     * Perform the import.
     *
     * @return array A hash of UID => id.
     */
    public function process()
    {
        return $this->_process();
    }

    /**
     * Process the iCalendar data.
     *
     * @return array A hash of UID => id.
     * @throws Kronolith_Exception
     */
    protected function _process()
    {
        $ids = array();
        $components = $this->_iCal->getComponents();
        if (count($components) == 0) {
            throw new Kronolith_Exception(_("No iCalendar data was found."));
        }
        foreach ($components as $component) {
            if (!$this->_preSave($component)) {
                continue;
            }

            try {
                // RECURRENCE-ID - must import after base event is
                // imported/saved so defer these until all other data is
                // processed.
                $component->getAttribute('RECURRENCE-ID');
                $this->_exceptions[] = $component;
            } catch (Horde_Icalendar_Exception $e) {
                $event = $this->_driver->getEvent();
                $event->fromiCalendar($component, true);
                // Delete existing exception events. There is no efficient way
                // to determine if any existing events have been changed/deleted
                // so we just remove them all since they will be re-added during
                // the import process.
                foreach ($event->boundExceptions() as $exception) {
                    $this->_driver->deleteEvent($exception->id);
                }

                // Save and post-process.
                $event->save();
                $this->_postSave($event);
                $ids[$event->uid] = $event->id;
            }
        }

        // Save exception events.
        foreach ($this->_exceptions as $exception) {
            $event = $this->_driver->getEvent();
            $event->fromiCalendar($exception);
            $event->save();
        }

        return $ids;
    }

    /**
     * Responsible for any logic needed after each event is saved. Only called
     * when base event (or an event with no recurrence) is saved. Exception
     * events are not passed.
     *
     * @param  Kronolith_Event $event  The event object.
     */
    protected function _postSave(Kronolith_Event $event)
    {
        // noop
    }

    /**
     * Responsible for any logic needed before the event is saved. Called for
     * EVERY component in the iCalendar object. Returning false from this method
     * will cause the current component to be ignored. Returning true causes it
     * to be processed.
     *
     * @param  Horde_Icalendar $component  The iCalendar component.
     *
     * @return boolean  True to continue processing, false to ignore.
     */
    protected function _preSave($component)
    {
        return ($component instanceof Horde_Icalendar_Vevent);
    }

}