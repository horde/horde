<?php
/**
 * Report using tickets
 *
 * $Horde: ansel/lib/Report/tickets.php,v 1.3 2008/05/07 01:45:56 chuck Exp $
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Report_tickets extends Ansel_Report {

    /**
     * Report
     */
    function report($message)
    {
        $info = array_merge($GLOBALS['conf']['report_content']['ticket_params'],
                            array('summary' => $this->getTitle(),
                                    'comment' => $message,
                                    'user_email' => $this->getUserEmail()));

        return $registry->call('tickets/addTicket', array($info));
    }

}
