<?php
/**
 * Login tasks module that purges old events.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Kronolith
 */
class Kronolith_LoginTasks_Task_PurgeEvents extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['prefs']->getValue('purge_events');
        if ($this->active) {
            $this->interval = $GLOBALS['prefs']->getValue('purge_events_interval');
            if ($GLOBALS['prefs']->isLocked('purge_events')) {
                $this->display = Horde_LoginTasks::DISPLAY_NONE;
            }
        }
    }

    /**
     * Purge old events.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function execute()
    {
        /* Get the current time minus the number of days specified in
         * 'purge_events_keep'.  An event will be deleted if it has an end
         * time prior to this time. */
        $del_time = new Horde_Date($_SERVER['REQUEST_TIME']);
        $del_time->mday -= $GLOBALS['prefs']->getValue('purge_events_keep');

        /* Need to have Horde_Perms::DELETE on a calendar to delete events
         * from it */
        $calendars = Kronolith::listInternalCalendars(true, Horde_Perms::DELETE);

        /* Start building the search */
        $kronolith_driver = Kronolith::getDriver();
        $query = new StdClass();
        $query->start = null;
        $query->end = $del_time;
        $query->status = null;
        $query->calendars = array(Horde_String::ucfirst($GLOBALS['conf']['calendar']['driver']) => array_keys($calendars));
        $query->creator = $GLOBALS['registry']->getAuth();

        /* Perform the search */
        $days = Kronolith::search($query);
        $count = 0;
        foreach ($days as $events) {
            foreach ($events as $event) {
                /* Delete if no recurrence, or if we are past the last occurence */
                if (!$event->recurs() ||
                    $event->recurrence->nextRecurrence($del_time) == false) {

                    if ($event->calendar != $kronolith_driver->calendar) {
                        $kronolith_driver->open($event->calendar);
                    }
                    try {
                        $kronolith_driver->deleteEvent($event->id, true);
                        ++$count;
                    } catch (Exception $e) {
                        Horde::logMessage($e, 'ERR');
                        throw $e;
                    }
                }
            }
        }

        $GLOBALS['notification']->push(sprintf(ngettext("Deleted %d event older than %d days.", "Deleted %d events older than %d days.", $count), $count, $GLOBALS['prefs']->getValue('purge_events_keep')));
    }

    /**
     * Return information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        return sprintf(_("All of your events older than %d days will be permanently deleted."),
                       $GLOBALS['prefs']->getValue('purge_events_keep'));
    }

}
