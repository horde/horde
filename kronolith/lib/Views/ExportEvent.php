<?php
/**
 * The Kronolith_View_ExportEvent:: class provides an API for exporting
 * events.
 *
 * @author  Jan Schneider <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_ExportEvent {

    /**
     * @param Kronolith_Event &$event
     */
    function Kronolith_View_ExportEvent(&$event)
    {
        require_once 'Horde/iCalendar.php';
        $iCal = new Horde_iCalendar('2.0');

        if (!$event->isRemote()) {
            $share = &$GLOBALS['kronolith_shares']->getShare($event->getCalendar());
            if (!is_a($share, 'PEAR_Error')) {
                $iCal->setAttribute('X-WR-CALNAME',
                                    Horde_String::convertCharset($share->get('name'),
                                                           NLS::getCharset(),
                                                           'utf-8'));
            }
        }

        $vEvent = &$event->toiCalendar($iCal);
        $iCal->addComponent($vEvent);
        $content = $iCal->exportvCalendar();

        $GLOBALS['browser']->downloadHeaders(
            $event->getTitle() . '.ics',
            'text/calendar; charset=' . NLS::getCharset(),
            true, strlen($content));
        echo $content;
        exit;
    }

}
