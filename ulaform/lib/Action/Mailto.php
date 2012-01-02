<?php
/**
 * Ulaform_Action_Mailto Class provides a Ulaform action driver to mail the
 * results of a form to one or more recipients.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */
class Ulaform_Action_Mailto extends Ulaform_Action {

    /**
     * Actually carry out the action.
     */
    public function doAction($form_params, $form_data, $fields)
    {
        global $conf;

        $mail = new Horde_Mime_Mail();
        $mail->addHeader('From', $form_params['from']);
        $mail->addHeader('Subject', $form_params['subject']);
        $mail->addHeader('To', $form_params['to']);
        if (!empty($form_params['cc'])) {
            $mail->addHeader('Cc', $form_params['cc']);
        }
        if (!empty($form_params['bcc'])) {
            $mail->addHeader('Bcc', $form_params['bcc']);
        }

        $body = '';
        foreach ($fields as $field) {
            $value = array_shift($form_data);
            switch ($field['field_type']) {
            case 'file':
            case 'image':
                if (!empty($value['file'])) {
                    $mail->addAttachment($value['file'], $value['name'], $value['type']);
                }
                break;

            default:
                $body .= $field['field_label'] . ': ' .
                         $this->_formatFormData($value) . "\n";
                break;
            }
        }

        $mail->setBody($body);
        return $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
    }

    /**
     * Identifies this action driver and returns a brief description, used by
     * admin when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    static public function getInfo()
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
    static public function getParams()
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
    protected function _formatFormData($field_data)
    {
        $body = '';
        if (!is_array($field_data)) {
            $body = $field_data;
        } else {
            foreach ($field_data as $data) {
                $body .= empty($body) ? $this->_formatFormData($data)
                            : ', ' . $this->_formatFormData($data);
            }
        }
        return $body;
    }

}
