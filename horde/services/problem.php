<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/* Send the browser back to the correct page. */
function _returnToPage()
{
    $url = new Horde_Url(Horde_Util::getFormData('return_url', Horde::url('login.php', true, array('app' => 'horde'))));
    $url->redirect();
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

if (!Horde_Menu::showService('problem')) {
    _returnToPage();
}

$identity = $injector->getInstance('Horde_Prefs_Identity')->getIdentity();
$email = $identity->getValue('from_addr');
if (!$email) {
    $email = Horde_Util::getFormData('email', $registry->getAuth());
}
$message = Horde_Util::getFormData('message', '');
$name = Horde_Util::getFormData('name', $identity->getValue('fullname'));
$subject = Horde_Util::getFormData('subject', '');

$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'send_problem_report':
    if ($subject && $message) {
        /* This is not a gettext string on purpose. */
        $remote = (!empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_HOST'] : $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $body = "This problem report was received from $remote. " .
            "The user clicked the problem report link from the following location:\n" .
            Horde_Util::getFormData('return_url', 'No requesting page') .
            "\nand is using the following browser:\n$user_agent\n\n" .
            str_replace("\r\n", "\n", $message);

        /* Default to a relatively reasonable email address. */
        if (!$email) {
            $email = 'horde-problem@' . $conf['problems']['maildomain'];
        }

        /* Check for attachments. */
        $attachment = null;
        if (!empty($conf['problems']['attachments'])) {
            try {
                $browser->wasFileUploaded('attachment', _("attachment"));
                $attachment = $_FILES['attachment'];
            } catch (Horde_Browser_Exception $e) {
                if ($e->getCode() != UPLOAD_ERR_NO_FILE) {
                    $notification->push($e, 'horde.error');
                    break;
                }
            }
        }

        if (!empty($conf['problems']['tickets']) &&
            $registry->hasMethod('tickets/addTicket')) {
            $info = array_merge($conf['problems']['ticket_params'],
                                array('summary' => $subject,
                                      'comment' => $body,
                                      'user_email' => $email));

            try {
                $registry->call('tickets/addTicket', array($info));
            } catch (Horde_Exception $e) {
                $notification->push($e);
                break;
            }

            if ($attachment &&
                $registry->hasMethod('tickets/addAttachment')) {
                try {
                    $registry->call(
                        'tickets/addAttachment',
                        array(
                            'ticket_id' => $result,
                            'name' => $attachment['name'],
                            'data' => file_get_contents($attachment['tmp_name'])
                        )
                    );
                } catch (Horde_Exception $e) {
                    $notification->push($e);
                }
            }

            _returnToPage();
        } else {
            /* Add user's name to the email address if provided. */
            if ($name) {
                @list($mailbox, $host) = @explode('@', $email, 2);
                if (empty($host)) {
                    $host = $conf['problems']['maildomain'];
                }
                $email = Horde_Mime_Address::writeAddress($mailbox, $host, $name);
            }

            $mail = new Horde_Mime_Mail(array('subject' => _("[Problem Report]") . ' ' . $subject,
                                              'body' => $body,
                                              'to' => $conf['problems']['email'],
                                              'from' => $email,
                                              'charset' => $GLOBALS['registry']->getCharset()));
            $mail->addHeader('Sender', 'horde-problem@' . $conf['problems']['maildomain']);

            /* Add attachment. */
            if ($attachment) {
                $mail->addAttachment($attachment['tmp_name'],
                                     $attachment['name'],
                                     $attachment['type']);
            }

            try {
                $mail->send($injector->getInstance('Horde_Mail'));

                /* We succeeded. */
                Horde::logMessage(
                    sprintf("%s Message sent to %s from %s",
                            $_SERVER['REMOTE_ADDR'],
                            preg_replace('/^.*<([^>]+)>.*$/', '$1', $conf['problems']['email']),
                            preg_replace('/^.*<([^>]+)>.*$/', '$1', $email)),
                    __FILE__, __LINE__, 'INFO');

                /* Return to previous page and exit this script. */
                _returnToPage();
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }
    }
    break;

case 'cancel_problem_report':
    _returnToPage();
}

$title = _("Problem Description");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/menu/menu.inc';
$notification->notify(array('listeners' => 'status'));
require HORDE_TEMPLATES . '/problem/problem.inc';
require HORDE_TEMPLATES . '/common-footer.inc';
