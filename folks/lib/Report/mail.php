<?php
/**
 * Report by email class
 *
 * $Horde: Folks/lib/Report/mail.php,v 1.7 2008/07/03 04:17:52 mrubinsk Exp $
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Report_mail extends Folks_Report {

    /**
     * Report
     */
    public function report($message, $users = array())
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
        $mail = new Horde_Mime_Mail($this->getTitle(), $this->getMessage($message), $to, $this->getUserEmail());

        //FIXME: This address should be configurable
        $mail->addHeader('Sender', 'horde-problem@' . $conf['report_content']['maildomain']);

        $mail_driver = $conf['mailer']['type'];
        $mail_params = $conf['mailer']['params'];
        if ($mail_driver == 'smtp' && $mail_params['auth'] &&
            empty($mail_params['username'])) {
            if (Auth::getAuth()) {
                $mail_params['username'] = Auth::getAuth();
                $mail_params['password'] = Auth::getCredential('password');
            } elseif (!empty($conf['report_content']['username']) &&
                        !empty($conf['report_content']['password'])) {
                $mail_params['username'] = $conf['report_content']['username'];
                $mail_params['password'] = $conf['report_content']['password'];
            }
        }

        return $mail->send($mail_driver, $mail_params);
    }
}