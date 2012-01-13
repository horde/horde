<?php
/**
 * Ulaform external API interface.
 *
 * This file defines Ulaform's external API interface. Other applications can
 * interact with Ulaform through this API.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
        return $GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create()->getForms();
    }

    /**
     * Get form fields
     *
     * @param integer $form_id Form id to get fields for
     */
    public function getFormFields($form_id)
    {
        try {
            $fields = $GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create()->getFields($form_id);
        } catch (Ulaform_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
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
        /* Get the stored form information from the backend. */
        try {
            $form_info = $GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create()->getForm($form_id, Horde_Perms::READ);
        } catch (Horde_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
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
        $vars->set('user_uid', $GLOBALS['registry']->getAuth());
        $vars->set('email', $GLOBALS['prefs']->getValue('from_addr'));

        try {
            $fields = $GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create()->getFields($form_id);
        } catch (Ulaform_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
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
            try {
                $GLOBALS['ulaform_driver']->submitForm($info);
                return true;
            } catch (Horde_Exception $e) {
                throw new Ulaform_Exception(sprintf(_("Error submitting form. %s."), $e->getMessage()));
            }
        }

        if (is_null($target_url)) {
            $target_url = Horde::selfUrl(true);
        }

        Horde::startBuffer();
        $form->renderActive(null, null, $target_url, 'post', 'multipart/form-data');
        return array('title' => $form_info['form_name'],
                    'form' => Horde::endBuffer());
    }

}