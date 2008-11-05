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

        require_once 'Horde/MIME.php';
        require_once 'Horde/MIME/Headers.php';
        require_once 'Horde/MIME/Message.php';

        $email = $this->getUserEmail();

        $msg_headers = new MIME_Headers();
        $msg_headers->addReceivedHeader();
        $msg_headers->addMessageIdHeader();
        $msg_headers->addAgentHeader();
        $msg_headers->addHeader('Date', date('r'));
        $msg_headers->addHeader('To', $to);
        $msg_headers->addHeader('Subject', $this->getTitle());
        $msg_headers->addHeader('From', $email);

        //FIXME: This address should be configurable
        $msg_headers->addHeader('Sender', 'horde-problem@' . $conf['report_content']['maildomain']);

        $mime = new MIME_Message();
        $mime->addPart(
            new MIME_Part('text/plain',
                          String::wrap($this->getMessage($message), 80, "\n"),
                          NLS::getCharset()));
        $msg_headers->addMIMEHeaders($mime);

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

        return $mime->send($conf['report_content']['email'],
                           $msg_headers, $mail_driver, $mail_params);
    }
}