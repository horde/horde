<?php
/**
 * The Ulaform:: class providing some support functions to the Ulaform module.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/Ulaform.php,v 1.42 2009-12-01 12:52:42 jan Exp $
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @since   Ulaform 0.1
 * @package Ulaform
 */
class Ulaform {

    function getActionInfo($action)
    {
        static $info = array();
        if (isset($info[$action])) {
            return $info[$action];
        }

        require_once dirname(__FILE__) . '/Action/' . $action . '.php';
        $class = 'Ulaform_Action_' . $action;
        $info[$action] = call_user_func(array($class, 'getInfo'));

        return $info[$action];
    }

    function getActionParams($action)
    {
        static $params = array();
        if (isset($params[$action])) {
            return $params[$action];
        }

        require_once dirname(__FILE__) . '/Action/' . $action . '.php';
        $class = 'Ulaform_Action_' . $action;
        $params[$action] = call_user_func(array($class, 'getParams'));

        return $params[$action];
    }

    /**
     * Fetch the available field types from the Horde_Form classes.
     *
     * @return array  The available field types.
     */
    function getFieldTypes()
    {
        static $available_fields = array();
        if (!empty($available_fields)) {
            return $available_fields;
        }

        /* Fetch the field type information from the Horde_Form classes. */
        $fields = Ulaform::getFieldTypesArray();

        /* Strip out the name element from the array. */
        foreach ($fields as $field_type => $info) {
            $available_fields[$field_type] = $info['name'];
        }

        /* Sort for display purposes. */
        asort($available_fields);

        return $available_fields;
    }

    /**
     * Fetch the full array for the field types, with params.
     *
     * @return array  The full field types array.
     */
    function getFieldTypesArray()
    {
        static $fields_array = array();
        if (!empty($fields_array)) {
            return $fields_array;
        }

        /* Fetch all declared classes. */
        $classes = get_declared_classes();

        /* Filter for the Horde_Form_Type classes. */
        foreach ($classes as $class) {
            if (strtolower(substr($class, 0, 16)) == 'horde_form_type_') {
                $field_type = substr($class, 16);
                /* Don't bother including the types that cannot be handled
                   usefully by ulaform. */
                if ($field_type == 'invalid') {
                    continue;
                }
                $fields_array[$field_type] = @call_user_func(array('Horde_Form_Type_' . $field_type, 'about'));
            }
        }

        return $fields_array;
    }

    function getFieldParams($field_type)
    {
        $fields = Ulaform::getFieldTypesArray();

        /* Return null if there are no params for this field type. */
        if (!isset($fields[$field_type]['params'])) {
            return array();
        }

        return $fields[$field_type]['params'];
    }

    function getStringlistArray($string)
    {
        $string = str_replace("'", "\'", $string);
        $values = explode(',', $string);

        foreach ($values as $value) {
            $value = trim($value);
            $value_array[$value] = $value;
        }

        return $value_array;
    }

    /**
     * This function does the permission checking when using Ulaform.
     *
     * @param mixed  $in          A form_id or an array of form_id's to check
     *                            permission on.
     * @param string $filter      What type of check to do.
     * @param string $permission  What type of permission to check for.
     * @param string $key         The array key to use for checking.
     *
     * @return mixed  Depending on the type of check, either return a boolean
     *                to indicate permission for that form, or a filtered out
     *                array of form_id's.
     */
    function checkPermissions($in, $filter, $permission = Horde_Perms::READ, $key = null)
    {
        static $permsCache;

        $admin = Horde_Auth::isAdmin();
        /* Horde admin is always authorised. */
        if ($admin) {
            return $in;
        }

        $out = array();
        $userID = Horde_Auth::getAuth();
        switch ($filter) {
        /* Check permissions for a single form or for an array of forms. */
        case 'form':
            if (is_array($in)) {
                $id = serialize($in);
            } else {
                $id = $in;
                $in = array($in);
            }

            if (isset($permsCache[$id][$permission])) {
                return $permsCache[$id][$permission];
            }

            foreach ($in as $form_id => $form) {
                if (!is_null($key)) {
                    $form_id = $form[$key];
                }
                if ($GLOBALS['perms']->hasPermission('ulaform:form:' . $form_id, $userID, $permission)) {
                    /* Cache and set into the $out array. */
                    $permsCache[$form_id][$permission] = true;
                    $out[$form_id] = $form;
                }
            }
            $permsCache[$id][$permission] = $out;
            break;

        default:
            /* For now default everything to false */
            $out = false;
        }

        return $out;
    }

    /**
     * Get a list of fields that belong to a forms.
     *
     * @return array  Array of the available fields for a specific
     *                form.
     */
    function getFieldsList($form_id)
    {
        $form = $GLOBALS['ulaform_driver']->getForm($form_id);
        if (is_a($form, 'PEAR_Error')) {
            return $form;
        }

        $fields = $GLOBALS['ulaform_driver']->getFields($form_id);
        if (is_a($fields, 'PEAR_Error')) {
            return $fields;
        }

        $fields_list = array();
        $i = 0;
        foreach ($fields as $field) {
            $url_params = array('form_id' => $form_id,
                                'field_id' => $field['field_id']);
            $fields_list[$i] = array(
                'del_url' => Horde_Util::addParameter(Horde::applicationUrl('deletefield.php'), $url_params),
                'edit_url' => Horde_Util::addParameter(Horde::applicationUrl('fields.php'), $url_params),
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
     * Build Ulaform's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);

        $menu->addArray(array('url' => Horde::applicationUrl('forms.php'), 'text' => _("_List Forms"), 'icon' => 'ulaform.png'));
        $menu->addArray(array('url' => Horde::applicationUrl('edit.php'), 'text' => _("_New Form"), 'icon' => 'new.png'));

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
