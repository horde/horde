<?php
/**
 * The Kronolith_View_ExportEvent:: class provides an API for exporting
 * events.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_View_ExportEvent
{
    /**
     * @param mixed Kronolith_Event|string $event  The event object or error
     *                                             string to display.
     */
    public function __construct($event)
    {
        if (!$event) {
            echo '<h3>' . _("Event not found") . '</h3>';
            exit;
        }
        if (is_string($event)) {
            echo '<h3>' . $event . '</h3>';
            exit;
        }

        $iCal = new Horde_Icalendar('2.0');

        if ($event->calendarType == 'internal') {
            try {
                $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($event->calendar);
                $iCal->setAttribute('X-WR-CALNAME', $share->get('name'));
            } catch (Exception $e) {
            }
        }

        $iCal->addComponent($event->toiCalendar($iCal));
        $content = $iCal->exportvCalendar();

        $GLOBALS['browser']->downloadHeaders(
            $event->getTitle() . '.ics',
            'text/calendar; charset=UTF-8',
            true, strlen($content));
        echo $content;
        exit;
    }

}
