<?php
/**
 * Problem reporting page.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$redirect_url = new Horde_Url(Horde_Util::getFormData('return_url', Horde::url('login.php', true, array('app' => 'horde'))));

if (!$registry->showService('problem')) {
    $redirect_url->redirect();
}

$vars = Horde_Variables::getDefaultVariables();

$identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
$email = $identity->getValue('from_addr');
if (!$email) {
    $email = $vars->get('email', $registry->getAuth());
}
$message = $vars->message;
$name = $vars->get('name', $identity->getValue('fullname'));
$subject = $vars->subject;

switch ($vars->actionID) {
case 'send_problem_report':
    if ($subject && $message) {
        /* This is not a gettext string on purpose. */
        $remote = empty($_SERVER['REMOTE_HOST'])
            ? $_SERVER['REMOTE_ADDR']
            : $_SERVER['REMOTE_HOST'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $body = "This problem report was received from $remote. " .
            "The user clicked the problem report link from the following location:\n" .
            $vars->get('return_url', 'No requesting page') .
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
                $info = array_merge($conf['problems']['ticket_params'], array(
                    'summary' => $subject,
                    'comment' => $body,
                    'user_email' => $email
                ));

            try {
                $ticketId = $registry->call('tickets/addTicket', array($info));
            } catch (Horde_Exception $e) {
                $notification->push($e);
                break;
            }

            if ($attachment &&
                $registry->hasMethod('tickets/addAttachment')) {
                try {
                    $registry->call('tickets/addAttachment', array(
                        'ticket_id' => $ticketId,
                        'name' => $attachment['name'],
                        'data' => file_get_contents($attachment['tmp_name'])
                    ));
                } catch (Horde_Exception $e) {
                    $notification->push($e);
                }
            }

            $redirect_url->redirect();
        }

        /* Add user's name to the email address if provided. */
        if ($name) {
            $addr_ob = new Horde_Mail_Rfc822_Address($email);
            if (is_null($addr_ob->host)) {
                $addr_ob->host = $conf['problems']['maildomain'];
            }
            $addr_ob->personal = $name;
            $email = $addr_ob->writeAddress(true);
        }

        $mail = new Horde_Mime_Mail(array(
            'body' => $body,
            'Subject' => _("[Problem Report]") . ' ' . $subject,
            'To' => $conf['problems']['email'],
            'From' => $email
        ));
        $mail->addHeader('Sender', 'horde-problem@' . $conf['problems']['maildomain']);

        /* Add attachment. */
        if ($attachment) {
            $mail->addAttachment(
                $attachment['tmp_name'],
                $attachment['name'],
                $attachment['type']
            );
        }

        try {
            $mail->send($injector->getInstance('Horde_Mail'));

            /* Success. */
            Horde::logMessage(
                sprintf("%s Message sent to %s from %s",
                    $_SERVER['REMOTE_ADDR'],
                    preg_replace('/^.*<([^>]+)>.*$/', '$1', $conf['problems']['email']),
                    preg_replace('/^.*<([^>]+)>.*$/', '$1', $email)
                ),
                'INFO'
            );

            /* Return to previous page and exit this script. */
            $redirect_url->redirect();
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'cancel_problem_report':
    $redirect_url->redirect();
    break;
}

$page_output->sidebar = false;
$page_output->addInlineJsVars(array(
    'HordeProblem.message_text' => _("You must describe the problem before you can send the problem report."),
    'HordeProblem.summary_text' => _("Please provide a summary of the problem.")
), true);
$page_output->addScriptFile('problem.js', 'horde');

$page_output->header(array(
    'title' => _("Problem Description")
));
$notification->notify(array('listeners' => 'status'));
require HORDE_TEMPLATES . '/problem/problem.inc';
$page_output->footer();
