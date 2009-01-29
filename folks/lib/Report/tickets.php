<?php
/**
 * Report using tickets
 *
 * $Horde: Folks/lib/Report/tickets.php,v 1.3 2008/05/07 01:45:56 chuck Exp $
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Report_tickets extends Folks_Report {

    /**
     * Report
     */
    public function report($message)
    {
        $info = array_merge($GLOBALS['conf']['report_content']['ticket_params'],
                            array('summary' => $this->getTitle(),
                                    'comment' => $message,
                                    'user_email' => $this->getUserEmail()));

        return $registry->call('tickets/addTicket', array($info));
    }

}