<?php
/**
 * Report using tickets
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

        return $GLOBALS['registry']->call('tickets/addTicket', array($info));
    }

}
