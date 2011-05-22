<?php
/**
 * Ulaform_Action_mailto Class provides a Ulaform action driver to mail the
 * results of a form to one or more recipients.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/Action/mailto.php,v 1.22 2009-07-09 08:18:44 slusarz Exp $
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */
class Ulaform_Action_mailto extends Ulaform_Action {

    /**
     * Actually carry out the action.
     */
    function doAction($form_params, $form_data, $fields)
    {
        global $conf;

        /* Get the required libs. */
        require_once 'Horde/MIME.php';
        require_once 'Horde/MIME/Message.php';

        $recipients = array();

        $message = &new MIME_Message();

        $headers['From'] = MIME::encodeAddress($form_params['from']);
        $headers['To'] = MIME::encodeAddress($form_params['to']);
        $recipients[] = $headers['To'];
        $headers['Subject'] = MIME::encode($form_params['subject'], Horde_Nls::getCharset());
        if (!empty($form_params['cc'])) {
            $headers['Cc'] = MIME::encodeAddress($form_params['cc']);
            $recipients[] = $headers['Cc'];
        }
        if (!empty($form_params['bcc'])) {
            $headers['Bcc'] = MIME::encodeAddress($form_params['bcc']);
            $recipients[] = $headers['Bcc'];
        }

        $body = '';
        $have_parts = false;
        foreach ($fields as $field) {
            $value = array_shift($form_data);
            switch ($field['field_type']) {
            case 'file':
            case 'image':
                $have_parts = true;
                $part = &new MIME_Part();
                $part->setType($value['type']);

                $data = file_get_contents($value['file']);

                $part->setContents($data);
                $part->setDisposition('attachment');
                $part->setDispositionParameter('filename', $value['name']);
                $part->setContentTypeParameter('name', $value['name']);
                $message->addPart($part);
                break;

            default:
                $body .= $field['field_label'] . ': ' .
                         $this->formatFormData($value) . "\n";
                break;
            }
        }

        if ($have_parts) {
            $message->setType('multipart/mixed');
            $part = &new MIME_Part();
            $part->setType('text/plain');
            $part->setCharset(Horde_Nls::getCharset());
            $part->setDisposition('inline');
            $part->setContents($body);
            $message->addPart($part);
        } else {
            $message->setContents($body);
            $message->setType('text/plain');
            $message->setCharset(Horde_Nls::getCharset());
        }

        $headers = $message->header($headers);
        return $message->send(join(', ', $recipients), $headers);
    }

    /**
     * Identifies this action driver and returns a brief description, used by
     * admin when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getInfo()
    {
        $info['name'] = _("Mailto");
        $info['desc'] = _("This driver allows the sending of form results via email to one or more recipients.");

        return $info;
    }

    /**
     * Returns the required parameters for this action driver, used by admin
     * when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getParams()
    {
        $params = array();
        $params['subject'] = array('label' => _("Subject"), 'type' => 'text');
        $params['from']    = array('label' => _("From"), 'type' => 'email');
        $params['to']      = array('label' => _("To"),
                                   'type' => 'email',
                                   'params' => array(true));
        $params['cc']      = array('label' => _("Cc"),
                                   'type' => 'email',
                                   'required' => false,
                                   'params' => array(true));
        $params['bcc']     = array('label' => _("Bcc"),
                                   'type' => 'email',
                                   'required' => false,
                                   'params' => array(true));

        return $params;
    }

    /**
     * Returns an email-friendly string containing the field data supplied.
     * Mainly to deal with form data supplied in an array structure.
     *
     * @return string
     */
    function formatFormData($field_data)
    {
        $body = '';
        if (!is_array($field_data)) {
            $body = $field_data;
        } else {
            foreach ($field_data as $data) {
                $body .= empty($body) ? $this->formatFormData($data)
                            : ', ' . $this->formatFormData($data);
            }
        }
        return $body;
    }

}
