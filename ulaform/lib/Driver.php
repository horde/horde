<?php
/**
 * Ulaform_Driver Class
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
    protected $_params = array();

    protected $_driver;

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct($params)
    {
        $this->_params = $params;
        $this->_driver = $GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create();
    }

    /**
     * Get a list of forms.
     *
     * @return array  Array of the available forms.
     * @throws Ulaform_Exception
     */
    public function getFormsList()
    {
        $forms = $this->_driver->getForms();

        $forms_list = array();
        $i = 0;
        foreach ($forms as $form) {
            $forms_list[$i]['id'] = $form['form_id'];
            $forms_list[$i]['del_url'] = Horde::url('delete.php')->add('form_id', $form['form_id']);
            $forms_list[$i]['edit_url'] = Horde::url('edit.php')->add('form_id', $form['form_id']);
            $forms_list[$i]['preview_url'] = Horde::url('display.php')->add('form_id', $form['form_id']);
            $forms_list[$i]['html_url'] = Horde::url('genhtml.php')->add('form_id', $form['form_id']);
            $forms_list[$i]['view_url'] = Horde::url('fields.php')->add('form_id', $form['form_id']);
            $forms_list[$i]['name'] = $form['form_name'];
            $forms_list[$i]['action'] = $form['form_action'];
            $forms_list[$i]['onsubmit'] = $form['form_onsubmit'];
            $i++;
        }

        return $forms_list;
    }

    /**
     * Get a list of fields that belong to a forms.
     *
     * @return array  Array of the available fields for a specific
     *                form.
     * @throws Horde_Exception_PermissionDenied
     * @throws Horde_Exception_NotFound
     * @throws Ulaform_Exception
     */
    public function getFieldsList($form_id)
    {
        $form = $this->_driver->getForm($form_id);
        $fields = $this->_driver->getFields($form_id);

        $fields_list = array();
        $i = 0;
        foreach ($fields as $field) {
            $url_params = array('form_id' => $form_id,
                                'field_id' => $field['field_id']);
            $fields_list[$i] = array(
                'del_url' => Horde::url('deletefield.php')->add($url_params),
                'edit_url' => Horde::url('fields.php')->add($url_params),
                'id' => $field['field_id'],
                'name' => $field['field_name'],
                'label' => $field['field_label'],
                'type' => $field['field_type'],
                'required' => $field['field_required'] ? _("Yes") : _("No"),
                'readonly' => $field['field_readonly'] ? _("Yes") : _("No"));
            $i++;
        }

        return $fields_list;
    }

    /**
     * Get a list of fields that belong to a forms as a simple
     * array.
     *
     * @return array  Array of the available fields for a specific
     *                form.
     * @throws Horde_Exception_PermissionDenied
     * @throws Horde_Exception_NotFound
     * @throws Ulaform_Exception
     */
    public function getFieldsArray($form_id)
    {
        $form = $this->_driver->getForm($form_id);
        $fields = $this->_driver->getFields($form_id);

        $fields_array = array();
        foreach ($fields as $field) {
            $fields_array[$field['field_id']] = $field['field_name'];
        }

        return $fields_array;
    }

    public function getField($form_id, $field_id)
    {
        $field = $this->_driver->getFields($form_id, $field_id);

        /* If we have a record. */
        if (isset($field[0])) {
            return $field[0];
        }

        return $field;
    }

    public function submitForm($form_data)
    {
        $form = $this->_driver->getForm($form_data['form_id']);
        $fields = $this->_driver->getFields($form_data['form_id']);

        $action = $GLOBALS['injector']->getInstance('Ulaform_Factory_Action')->create($form['form_action']);

        return $action->doAction($form['form_params'], $form_data, $fields);
    }

    public function hasPermission($perm = Horde_Perms::SHOW, $form_id = null)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('ulaform:forms:' . $form_id)) {
            return ($perm & Horde_Perms::DELETE) ? false : true;
        }

        return $perms->hasPermission('ulaform:forms', $GLOBALS['registry']->getAuth(), $perm) ||
            $perms->hasPermission('ulaform:forms:' . $form_id, $GLOBALS['registry']->getAuth(), $perm);
    }

}
