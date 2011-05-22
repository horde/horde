<?php
/**
 * Ulaform_Driver Class
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/Driver.php,v 1.35 2009-06-10 05:25:20 slusarz Exp $
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @since   Ulaform 0.1
 * @package Ulaform
 */
class Ulaform_Driver {

    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this driver.
     */
    function Ulaform_Driver($params)
    {
        $this->_params = $params;
    }

    /**
     * Get a list of forms.
     *
     * @return array  Array of the available forms.
     */
    function getFormsList()
    {
        $forms = $this->getForms();
        if (is_a($forms, 'PEAR_Error')) {
            return $forms;
        }

        $forms_list = array();
        $i = 0;
        foreach ($forms as $form) {
            $forms_list[$i]['id'] = $form['form_id'];
            $forms_list[$i]['del_url'] = Horde_Util::addParameter(Horde::applicationUrl('delete.php'), 'form_id', $form['form_id']);
            $forms_list[$i]['edit_url'] = Horde_Util::addParameter(Horde::applicationUrl('edit.php'), 'form_id', $form['form_id']);
            $forms_list[$i]['preview_url'] = Horde_Util::addParameter(Horde::applicationUrl('display.php'), 'form_id', $form['form_id']);
            $forms_list[$i]['html_url'] = Horde_Util::addParameter(Horde::applicationUrl('genhtml.php'), 'form_id', $form['form_id']);
            $forms_list[$i]['view_url'] = Horde_Util::addParameter(Horde::applicationUrl('fields.php'), 'form_id', $form['form_id']);
            $forms_list[$i]['name'] = $form['form_name'];
            $forms_list[$i]['action'] = $form['form_action'];
            $forms_list[$i]['onsubmit'] = $form['form_onsubmit'];
            $i++;
        }

        return $forms_list;
    }

    /**
     * Get a list of fields that belong to a forms as a simple
     * array.
     *
     * @return array  Array of the available fields for a specific
     *                form.
     */
    function getFieldsArray($form_id)
    {
        $form = $this->getForm($form_id);
        if (is_a($form, 'PEAR_Error')) {
            return $form;
        }

        $fields = $this->getFields($form_id);
        if (is_a($fields, 'PEAR_Error')) {
            return $fields;
        }

        $fields_array = array();
        foreach ($fields as $field) {
            $fields_array[$field['field_id']] = $field['field_name'];
        }

        return $fields_array;
    }

    function getField($form_id, $field_id)
    {
        $field = $this->getFields($form_id, $field_id);
        if (is_a($field, 'PEAR_Error')) {
            return $field;
        }

        /* If we have a record. */
        if (isset($field[0])) {
            return $field[0];
        }

        return $field;
    }

    function submitForm($form_data)
    {
        $form = $this->getForm($form_data['form_id']);
        $fields = $this->getFields($form_data['form_id']);

        require_once ULAFORM_BASE . '/lib/Action.php';
        $action = &Ulaform_Action::singleton($form['form_action'], $this->_params);

        return $action->doAction($form['form_params'], $form_data, $fields);
    }

    /**
     * Attempts to return a concrete Ulaform_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Ulaform_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Ulaform_Driver  The newly created concrete Ulaform_Driver
     *                         instance, or false on error.
     */
    function &factory($driver, $params = array())
    {
        $driver = basename($driver);
        include_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Ulaform_Driver_' . $driver;
        if (class_exists($class)) {
            $ulaform = &new $class($params);
            return $ulaform;
        } else {
            Horde::fatal(PEAR::raiseError(sprintf(_("No such backend \"%s\" found"), $driver)), __FILE__, __LINE__);
        }
    }

    /**
     * Attempts to return a reference to a concrete Ulaform_Driver instance
     * based on $driver.
     *
     * It will only create a new instance if no Ulaform_Driver instance with
     * the same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Ulaform_Driver::singleton()
     *
     * @param string $driver  The type of concrete Ulaform_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Ulaform_Driver instance, or false on
     *                error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Ulaform_Driver::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
