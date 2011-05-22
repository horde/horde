<?php
/**
 * Ulaform external API interface.
 *
 * This file defines Ulaform's external API interface. Other applications can
 * interact with Ulaform through this API.
 *
 * $Horde: ulaform/lib/Api.php,v 1.3 2010-02-03 10:06:46 jan Exp $
 *
 * @package Ulaform
 */
class Ulaform_Api extends Horde_Registry_Api
{
    /**
     * Get available forms
     */
    public function getForms()
    {
        require_once dirname(__FILE__) . '/base.php';
        return $GLOBALS['ulaform_driver']->getForms();
    }

    /**
     * Get form fields
     *
     * @param integer $form_id Form id to get fields for
     */
    public function getFormFields($form_id)
    {
        require_once dirname(__FILE__) . '/base.php';

        $fields = $GLOBALS['ulaform_driver']->getFields($form_id);
        if (!is_a($fields, 'PEAR_Error')) {
            return $fields;
        }

        foreach ($fields as $id => $field) {
            /* In case of these types get array from stringlist. */
            if ($field['field_type'] == 'link' ||
                $field['field_type'] == 'enum' ||
                $field['field_type'] == 'multienum' ||
                $field['field_type'] == 'radio' ||
                $field['field_type'] == 'set' ||
                $field['field_type'] == 'sorter') {
                $fields[$id]['field_params']['values'] = Ulaform::getStringlistArray($field['field_params']['values']);
            }
        }

        return $fields;
    }

    /**
     * Display form
     *
     * @param integer $form_id      Form id dispaly
     * @param string $target_url    Target url to link form to
     */
    public function display($form_id, $target_url = null)
    {
        require_once dirname(__FILE__) . '/base.php';

        /* Get the stored form information from the backend. */
        $form_info = $GLOBALS['ulaform_driver']->getForm($form_id, Horde_Perms::READ);
        if (is_a($form_info, 'PEAR_Error')) {
            return $form_info;
        }

        if (!empty($form_info['form_params']['language'])) {
            Horde_Nls::setLanguageEnvironment($form_info['form_params']['language']);
        }

        $vars = Horde_Variables::getDefaultVariables();
        $vars->set('form_id', $form_id);

        $form = new Horde_Form($vars);
        $form->addHidden('', 'form_id', 'int', false);
        $form->addHidden('', 'user_uid', 'text', false);
        $form->addHidden('', 'email', 'email', false);
        $vars->set('user_uid', Horde_Auth::getAuth());
        $vars->set('email', $GLOBALS['prefs']->getValue('from_addr'));

        $fields = $GLOBALS['ulaform_driver']->getFields($form_id);
        if (is_a($fields, 'PEAR_Error')) {
            return $fields;
        }

        foreach ($fields as $field) {
            /* In case of these types get array from stringlist. */
            if ($field['field_type'] == 'link' ||
                $field['field_type'] == 'enum' ||
                $field['field_type'] == 'multienum' ||
                $field['field_type'] == 'mlenum' ||
                $field['field_type'] == 'radio' ||
                $field['field_type'] == 'set' ||
                $field['field_type'] == 'sorter') {
                $field['field_params']['values'] = Ulaform::getStringlistArray($field['field_params']['values']);
            }

            /* Setup the field with all the parameters. */
            $form->addVariable($field['field_label'], $field['field_name'], $field['field_type'], $field['field_required'], $field['field_readonly'], $field['field_desc'], $field['field_params']);
        }

        /* Check if submitted and validate. */
        $result = array('title' => $form_info['form_name']);
        if ($form->validate()) {
            $form->getInfo(null, $info);
            $submit = $GLOBALS['ulaform_driver']->submitForm($info);
            if (is_a($submit, 'PEAR_Error')) {
                PEAR::raiseError(sprintf(_("Error submitting form. %s."), $submit->getMessage()));
            } else {
                return true;
            }
        }

        if (is_null($target_url)) {
            $target_url = Horde::selfUrl(true);
        }

        return array('title' => $form_info['form_name'],
                    'form' => Horde_Util::bufferOutput(array($form, 'renderActive'), null, null, $target_url, 'post', 'multipart/form-data'));
    }

}