<?php
/**
 * Report by email class
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Report_mail extends Ansel_Report {

    /**
     * Report
     */
    function report($message, $users = array())
    {
        global $conf;

        if (empty($users)) {
            $to = $conf['report_content']['email'];
        } else {
            // we are sending a report to to the gallery owner, but fall back
            // to the admin in case the user has no email.
            $to = $this->_getUserEmail($users);
            if (empty($to)) {
                $to = $conf['report_content']['email'];
            }
        }

        $mail = new Horde_Mime_Mail(array(
            'body' => $this->getMessage($message),
            'Subject' => $this->getTitle(),
            'To' => $to,
            //FIXME: This address should be configurable
            'Sender' => 'horde-problem@' . $conf['report_content']['maildomain'],
            'From' => $this->getUserEmail()));

        return $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
    }
}
