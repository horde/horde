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

        /*
         * Needed for the Horde 4 mime library - use autoload everywhere we can
         * when this is *really* refactored for horde 4
         */
        $mail = new Horde_Mime_Mail(array('subject' => $this->getTitle(),
                                          'body' => $this->getMessage($message),
                                          'to' => $to,
                                          'from' => $this->getUserEmail()));

        //FIXME: This address should be configurable
        $mail->addHeader('Sender',
                         'horde-problem@' . $conf['report_content']['maildomain']);
        return $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
    }
}
