<?php
/**
 * Ulaform_Driver_sql Class
 *
 * $Horde: ulaform/lib/Driver/sql.php,v 1.47 2009-12-01 12:52:42 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */
class Ulaform_Driver_sql extends Ulaform_Driver {

    /**
     * Handle for the database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Saves the passed form into the db, either inserting a
     * new form if no form_id is available, or updating an
     * existing form if a form_id has been passed.
     *
     * @param array  $params  An array with the form details.
     *
     * @return boolean | PEAR_Error  True on success or the error on failure.
     */
    function saveForm(&$info)
    {
        $values = array();
        if (empty($info['form_id'])) {
            $info['form_id'] = $this->_db->nextId('ulaform_forms');
            if (is_a($info['form_id'], 'PEAR_Error')) {
                return $info['form_id'];
            }
            $sql = 'INSERT INTO ulaform_forms (form_id, user_uid, form_name, form_action, form_params, form_onsubmit) VALUES (?, ?, ?, ?, ?, ?)';
        } else {
            $sql = 'UPDATE ulaform_forms SET form_id = ?, user_uid = ?, form_name = ?, form_action = ?, form_params = ?, form_onsubmit = ? WHERE form_id = ?';
            $values[] = (int)$info['form_id'];
        }

        /* Serialize the form params. */
        require_once 'Horde/Serialize.php';
        $info['form_params'] = Horde_Serialize::serialize($info['form_params'], Horde_Serialize::UTF7_BASIC);

        array_unshift($values,
                      (int)$info['form_id'],
                      Horde_Auth::getAuth(),
                      Horde_String::convertCharset($info['form_name'], Horde_Nls::getCharset(), $this->_params['charset']),
                      $info['form_action'],
                      Horde_String::convertCharset($info['form_params'], Horde_Nls::getCharset(), $this->_params['charset']),
                      $info['form_onsubmit']);
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::saveForm(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $info['form_id'];
    }

    /**
     * Saves the passed field into the db, either inserting
     * a new field if no field_id is available, or updating
     * an existing field if a field_id has been passed.
     * If no form_id is available will return with error.
     *
     * @param array  $params  An array with the field details.
     *
     * @return boolean | PEAR_Error  True on success or the error on failure.
     */
    function saveField(&$info)
    {
        if (empty($info['form_id'])) {
            return PEAR::raiseError(_("Missing form"));
        }

        $values = array();
        if (empty($info['field_id'])) {
            $info['field_id'] = $this->_db->nextId('ulaform_fields');
            if (is_a($info['field_id'], 'PEAR_Error')) {
                return $info['field_id'];
            }
            if (empty($info['field_order'])) {
                $info['field_order'] = $this->nextFieldOrder($info['form_id']);
            }
            $sql = 'INSERT INTO ulaform_fields (field_id, form_id, field_name, field_label, field_type, field_params, field_required, field_readonly, field_desc, field_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        } else {
            $sql = 'UPDATE ulaform_fields SET field_id = ?, form_id = ?, field_name = ?, field_label = ?, field_type = ?, field_params = ?, field_required = ?, field_readonly = ?, field_desc = ?, field_order = ? WHERE field_id = ?';
            $values[] = $info['field_id'];
        }

        /* Set up the field data. */
        $info['field_required'] = ($info['field_required'] ? 1 : 0);
        $info['field_readonly'] = ($info['field_readonly'] ? 1 : 0);

        if (!empty($info['field_params'])) {
            require_once 'Horde/Serialize.php';
            $info['field_params'] = Horde_Serialize::serialize($info['field_params'], Horde_Serialize::UTF7_BASIC);
        } else {
            $info['field_params'] = null;
        }

        array_unshift($values,
                      $info['field_id'],
                      $info['form_id'],
                      Horde_String::convertCharset($info['field_name'], Horde_Nls::getCharset(), $this->_params['charset']),
                      Horde_String::convertCharset($info['field_label'], Horde_Nls::getCharset(), $this->_params['charset']),
                      $info['field_type'],
                      Horde_String::convertCharset($info['field_params'], Horde_Nls::getCharset(), $this->_params['charset']),
                      $info['field_required'],
                      $info['field_readonly'],
                      Horde_String::convertCharset($info['field_desc'], Horde_Nls::getCharset(), $this->_params['charset']),
                      $info['field_order']);
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::saveField(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Gets the next field order position within a form.
     *
     * @param integer  $form_id
     *
     * @return integer | PEAR_Error
     */
    function nextFieldOrder($form_id)
    {
        $sql = 'SELECT MAX(field_order) FROM ulaform_fields WHERE form_id = ?';
        $result = $this->_db->getOne($sql, array($form_id));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $result + 1;
    }

    /**
     * Sets the specified sort order to the fields in a form.
     *
     * @param array  $params  An array with the field ids in
     *                        a specific order.
     *
     * @return boolean | PEAR_Error  True on success or the error on failure.
     */
    function sortFields(&$info)
    {
        if (empty($info['form_id'])) {
            return PEAR::raiseError(_("Missing form"));
        }

        foreach ($info['field_order'] as $field_order => $field_id) {
            $sql = 'UPDATE ulaform_fields
                   SET field_order = ?
                   WHERE field_id = ?';
            Horde::logMessage('SQL Query by Ulaform_Driver_sql::sortFields(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $result = $this->_db->query($sql, array((int)$field_order, (int)$field_id));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Fetches the a list of available forms and the basic data.
     *
     * @return array  An array of the available forms.
     */
    function getForms($form_id = null)
    {
        $wsql = '';
        $values = array();
        if (!is_null($form_id)) {
            $wsql = ' WHERE form_id = ?';
            $values[] = (int)$form_id;
        }

        /* Get the forms. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms ' . $wsql;
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::getForms(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $result = Horde_String::convertCharset($result, $this->_params['charset']);
        return Ulaform::checkPermissions($result, 'form', Horde_Perms::SHOW, 'form_id');
    }

    /**
     * Fetches the a list of available forms and the basic data.
     *
     * @return array  An array of the available forms.
     */
    function formExists($form_id = null)
    {
        global $perms;

        $wsql = '';
        $values = array();
        if (!is_null($form_id)) {
            $wsql = ' WHERE form_id = ?';
            $values[] = (int)$form_id;
        }

        /* Get the forms. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms ' . $wsql;
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::getForms(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Check if the form exists
        if (empty($result)) {
            return PEAR::raiseError(sprintf(_("No such form ID \"%s\"."), $form_id));
        }

        $result = Horde_String::convertCharset($result, $this->_params['charset']);
        return Ulaform::checkPermissions($result, 'form', Horde_Perms::SHOW, 'form_id');
    }

    /**
     * Fetches the a list of available forms to use.
     *
     * @return array  An array of the available forms.
     */
    function getAvailableForms()
    {
        /* Fetch a list of all forms for now. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms';
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::getAvailableForms(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return $this->_db->getAll($sql, DB_FETCHMODE_ASSOC);
    }

    /**
     * Fetches all the data specific to the supplied form id.
     *
     * @param integer $form_id  The form id of the form to return.
     *
     * @return array            The form data.
     */
    function getForm($form_id, $permission = Horde_Perms::SHOW)
    {
        /* Chek permissions */
        if ($GLOBALS['perms']->exists('ulaform:form:' . $form_id) &&
            !$GLOBALS['perms']->hasPermission('ulaform:form:' . $form_id, Horde_Auth::getAuth(), $permission)) {
            return PEAR::RaiseError(_("You don't have the right permission to access this form."));
        }

        /* Get the main form data. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms WHERE form_id = ?';
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::getForm(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $form = $this->_db->getRow($sql, array((int)$form_id), DB_FETCHMODE_ASSOC);
        if (is_a($form, 'PEAR_Error')) {
            Horde::logMessage($form, __FILE__, __LINE__, PEAR_LOG_ERROR);
            return $form;
        } elseif (empty($form)) {
            // Check if the form exists
            return PEAR::raiseError(sprintf(_("No such form ID \"%s\"."), $form_id));
        }

        /* Convert charset. */
        $form = Horde_String::convertCharset($form, $this->_params['charset']);

        /* Unserialize the form params. */
        require_once 'Horde/Serialize.php';
        $form['form_params'] = Horde_Serialize::unserialize($form['form_params'], Horde_Serialize::UTF7_BASIC);

        return $form;
    }

    /**
     * Fetches the fields for a particular form.
     *
     * @param integer $form_id  The form id of the form to return.
     *
     * @return array            The fields.
     */
    function getFields($form_id, $field_id = null)
    {
        $values = array($form_id);
        $sql = 'SELECT field_id, form_id, field_name, field_order, field_label, field_type, '
            . ' field_params, field_required, field_readonly, field_desc FROM ulaform_fields '
            . ' WHERE form_id = ?';

        if (!is_null($field_id)) {
            $sql .= ' AND field_id = ?';
            $values[] = (int)$field_id;
        }
        $sql .= ' ORDER BY field_order';

        Horde::logMessage('SQL Query by Ulaform_Driver_sql::getFields(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $results = $this->_db->query($sql, $values);

        $fields = array();
        while ($field = $results->fetchRow(DB_FETCHMODE_ASSOC)) {
            /* Convert charset. */
            $field = Horde_String::convertCharset($field, $this->_params['charset']);

            /* If no internal name set, generate one using field_id. */
            if (empty($field['field_name'])) {
                $field['field_name'] = 'field_' . $field['field_id'];
            }

            /* Check if any params and unserialize, otherwise return null. */
            if (!empty($field['field_params'])) {
                require_once 'Horde/Serialize.php';
                $field['field_params'] = Horde_Serialize::unserialize($field['field_params'], Horde_Serialize::UTF7_BASIC);
            } else {
                $field['field_params'] = null;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Deletes a form and all of its fields from the database.
     *
     * @param integer $form_id  The form id of the form to delete.
     *
     * @return boolean | PEAR_Error  True on success or the error on failure.
     */
    function deleteForm($form_id)
    {
        /* Delete the form. */
        $sql = 'DELETE FROM ulaform_forms WHERE form_id = ?';
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::deleteForm(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $delete = $this->_db->query($sql, array((int)$form_id));
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Delete the fields for this form. */
        $sql = 'DELETE FROM ulaform_fields WHERE form_id = ?';
        Horde::logMessage('SQL Query by Ulaform_Driver_sql::deleteForm(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $delete = $this->_db->query($sql, array((int)$form_id));
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        return true;
    }

    /**
     * Deletes a field from the database.
     *
     * @param integer $field_id  The field id of the field to delete.
     *
     * @return boolean | PEAR_Error  True on success or the error on failure.
     */
    function deleteField($field_id)
    {
        /* Delete the field. */
        $sql = 'DELETE FROM ulaform_fields WHERE field_id = ?';

        Horde::logMessage('SQL Query by Ulaform_Driver_sql::deleteField(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        return $this->_db->query($sql, array((int)$field_id));
    }

    function initialise()
    {
        global $registry;

        Horde::assertDriverConfig($this->_params, 'sql',
            array('phptype'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_db = &DB::connect($this->_params,
                                  array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_db, 'PEAR_Error')) {
            Horde::fatal($this->_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_db->phptype) {
        case 'mssql':
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        return true;
    }

}
