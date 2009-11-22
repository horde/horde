<?php
/**
 * Login tasks module that purges old events.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_LoginTasks
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
     * Purge old messages in the Trash folder.
     *
     * @return boolean  Whether any messages were purged from the Trash folder.
     */
    public function execute()
    {
        /* Get the current time minus the number of days specified in
         * 'purge_events_keep'.  An event will be deleted if it has an end
         * time prior to this time. */
        $del_time = new Horde_Date($_SERVER['REQUEST_TIME']);
        $del_time->mday -= $GLOBALS['prefs']->getValue('purge_events_keep');

        /* Need to have Horde_Perms::DELETE on a calendar to delete events from it */
        $calendars = Kronolith::listCalendars(false, Horde_Perms::DELETE);

        /* Start building an event object to use for the search */
        $kronolith_driver = Kronolith::getDriver();
        $query = &$kronolith_driver->getEvent();
        $query->start = null;
        $query->end = $del_time;
        $query->status = null;
        $query->calendars = array_keys($calendars);
        $query->creatorID = Horde_Auth::getAuth();

        /* Perform the search */
        $events = Kronolith::search($query);
        $count = 0;
        foreach ($events as $event) {
            if (!$event->recurs()) {
                if ($event->getCalendar() != $kronolith_driver->getCalendar()) {
                    $kronolith_driver->open($event->getCalendar());
                }
                $results = $kronolith_driver->deleteEvent($event->getId(), true);
                ++$count;
                if (is_a($results, 'PEAR_Error')) {
                    Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $results;
                }
            }
        }

        $GLOBALS['notification']->push(sprintf(ngettext("Deleted %d event older than %d days.", "Deleted %d events older than %d days.", $count), $count, $GLOBALS['prefs']->getValue('purge_events_keep')));

        return true;
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
