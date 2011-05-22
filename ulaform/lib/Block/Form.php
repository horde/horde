<?php

/**
 * Ulaform Form Block Class
 *
 * This file provides an api to include a Ulaform created form into
 * any other Horde app through the Horde_Blocks, by extending the
 * Horde_Blocks class.
 *
 * $Horde: ulaform/lib/Block/form.php,v 1.32 2009-06-10 17:33:44 slusarz Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Horde_Block
 */
class Ulaform_Block_Form extends Horde_Core_Block {

    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Show Form");
    }

    function _params()
    {
        /* Available Forms for use in a block. */
        $params['form_id'] = array('name' => _("Form"),
                                   'type' => 'enum',
                                   'values' => array());

        $forms = $GLOBALS['ulaform_driver']->getAvailableForms();
        if (!is_a($forms, 'PEAR_Error')) {
            foreach ($forms as $form) {
                $params['form_id']['values'][$form['form_id']] = $form['form_name'];
            }
        }

        return $params;
    }

    function _title()
    {
        global $registry;
        $html = Horde::link(Horde::url($registry->getInitialPage(), true)) . $registry->get('name') . '</a>';
        return $html;
    }

    function _content()
    {
        $vars = Horde_Variables::getDefaultVariables();
        $formname = $vars->get('formname');
        $done = false;

        $form = new Horde_Form($vars);
        $fields = $GLOBALS['ulaform_driver']->getFields($this->_params['form_id']);
        foreach ($fields as $field) {
            /* In case of these types get array from stringlist. */
            if ($field['field_type'] == 'link' ||
                $field['field_type'] == 'enum' ||
                $field['field_type'] == 'multienum' ||
                $field['field_type'] == 'radio' ||
                $field['field_type'] == 'set' ||
                $field['field_type'] == 'sorter') {
                $field['field_params']['values'] = Ulaform::getStringlistArray($field['field_params']['values']);
            }

            /* Setup the field with all the parameters. */
            $form->addVariable($field['field_label'], $field['field_name'], $field['field_type'], $field['field_required'], $field['field_readonly'], $field['field_desc'], $field['field_params']);
        }

        if ($formname) {
            $form->validate($vars);

            if ($form->isValid() && $formname) {
                $form->getInfo($vars, $info);
                $info['form_id'] = $this->_params['form_id'];

                $submit = $GLOBALS['ulaform_driver']->submitForm($info);
                if (is_a($submit, 'PEAR_Error')) {
                    Horde::logMessage($submit, __FILE__, __LINE__, PEAR_LOG_ERR);
                    $GLOBALS['notification']->push(sprintf(_("Error submitting form. %s."), $submit->getMessage()), 'horde.error');
                } else {
                    $GLOBALS['notification']->push(_("Form submitted successfully."), 'horde.success');
                    $done = true;
                }
            }
        }

        /* Render active or inactive, depending if submitted or
         * not. */
        $render_type = ($done) ? 'renderInactive' : 'renderActive';

        /* Render the form. */
        $renderer = new Horde_Form_Renderer();
        $renderer->showHeader(false);
        return Horde_Util::bufferOutput(array($form, $render_type), $renderer, $vars, Horde::selfUrl(true), 'post');
    }

}
