<?php
/**
 * Ulaform_Driver_Sql Class
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Vilius Šumskas <vilius@lnk.lt>
 * @package Ulaform
 */
class Ulaform_Driver_Sql extends Ulaform_Driver {

    /**
     * The database connection object.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Charset
     *
     * @var string
     */
    protected $_charset;

    /**
     * Construct a new SQL storage object.
     *
     * @param array $params    The connection parameters
     *
     * @throws InvalidArguementException
     */
    public function __construct($params = array())
    {
        if (empty($params['db'])) {
            throw new InvalidArgumentException('Missing required connection parameter(s).');
        }
        $this->_db = $params['db'];
        $this->_charset = $params['charset'];
    }

    /**
     * Saves the passed form into the db, either inserting a
     * new form if no form_id is available, or updating an
     * existing form if a form_id has been passed.
     *
     * @param array  $info  An array with the form details.
     *
     * @return integer  The form id.
     * @throws Ulaform_Exception
     */
    public function saveForm(&$info)
    {
        $values = array();
        if (!empty($info['form_id'])) {
            $values[] = (int)$info['form_id'];
        }

        /* Serialize the form params. */
        $info['form_params'] = Horde_Serialize::serialize($info['form_params'], Horde_Serialize::UTF7_BASIC);

        array_unshift($values,
                      $GLOBALS['registry']->getAuth(),
                      Horde_String::convertCharset($info['form_name'], 'UTF-8', $this->_charset),
                      $info['form_action'],
                      Horde_String::convertCharset($info['form_params'], 'UTF-8', $this->_charset),
                      $info['form_onsubmit']);

        if (empty($info['form_id'])) {
            $sql = 'INSERT INTO ulaform_forms (user_uid, form_name, form_action, form_params, form_onsubmit) VALUES (?, ?, ?, ?, ?)';
            try {
                $info['form_id'] = $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        } else {
            $sql = 'UPDATE ulaform_forms SET user_uid = ?, form_name = ?, form_action = ?, form_params = ?, form_onsubmit = ? WHERE form_id = ?';
            try {
                $this->_db->execute($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        }

        return $info['form_id'];
    }

    /**
     * Saves the passed field into the db, either inserting
     * a new field if no field_id is available, or updating
     * an existing field if a field_id has been passed.
     * If no form_id is available will throw an exception.
     *
     * @param array  $params  An array with the field details.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception_NotFound
     * @throws Ulaform_Exception
     */
    public function saveField(&$info)
    {
        if (empty($info['form_id'])) {
            throw new Horde_Exception_NotFound(_("Missing form"));
        }

        $values = array();
        if (!empty($info['field_id'])) {
            $values[] = $info['field_id'];
        } else {
            if (empty($info['field_order'])) {
                $info['field_order'] = $this->_nextFieldOrder($info['form_id']);
            }
        }

        /* Set up the field data. */
        $info['field_required'] = ($info['field_required'] ? 1 : 0);
        $info['field_readonly'] = ($info['field_readonly'] ? 1 : 0);

        if (!empty($info['field_params'])) {
            $info['field_params'] = Horde_Serialize::serialize($info['field_params'], Horde_Serialize::UTF7_BASIC);
        } else {
            $info['field_params'] = null;
        }

        array_unshift($values,
                      $info['form_id'],
                      Horde_String::convertCharset($info['field_name'], 'UTF-8', $this->_charset),
                      Horde_String::convertCharset($info['field_label'], 'UTF-8', $this->_charset),
                      $info['field_type'],
                      Horde_String::convertCharset($info['field_params'], 'UTF-8', $this->_charset),
                      $info['field_required'],
                      $info['field_readonly'],
                      Horde_String::convertCharset($info['field_desc'], 'UTF-8', $this->_charset),
                      $info['field_order']);

        if (empty($info['field_id'])) {
            $sql = 'INSERT INTO ulaform_fields (form_id, field_name, field_label, field_type, field_params, field_required, field_readonly, field_desc, field_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            try {
                $this->_db->execute($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        } else {
            $sql = 'UPDATE ulaform_fields SET form_id = ?, field_name = ?, field_label = ?, field_type = ?, field_params = ?, field_required = ?, field_readonly = ?, field_desc = ?, field_order = ? WHERE field_id = ?';
            try {
                $this->_db->execute($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Sets the specified sort order to the fields in a form.
     *
     * @param array  $info  An array with the field ids in
     *                      a specific order.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception_NotFound
     * @throws Ulaform_Exception
     */
    public function sortFields(&$info)
    {
        if (empty($info['form_id'])) {
            throw new Horde_Exception_NotFound(_("Missing form"));
        }

        foreach ($info['field_order'] as $field_order => $field_id) {
            $sql = 'UPDATE ulaform_fields
                   SET field_order = ?
                   WHERE field_id = ?';
            try {
                $this->_db->execute($sql, array((int)$field_order, (int)$field_id));
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Fetches the a list of available forms and the basic data.
     *
     * @return array  An array of the available forms.
     * @throws Ulaform_Exception
     */
    public function getForms($form_id = null)
    {
        $wsql = '';
        $values = array();
        if (!is_null($form_id)) {
            $wsql = ' WHERE form_id = ?';
            $values[] = (int)$form_id;
        }

        /* Get the forms. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms' . $wsql;
        try {
            $result = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        return Ulaform::checkPermissions($result, 'form', Horde_Perms::SHOW, 'form_id');
    }

    /**
     * Fetches the a list of available forms to use.
     *
     * @return array  An array of the available forms.
     * @throws Ulaform_Exception
     */
    public function getAvailableForms()
    {
        /* Fetch a list of all forms for now. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms';
        try {
            return $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }
    }

    /**
     * Fetches all the data specific to the supplied form id.
     *
     * @param integer $form_id  The form id of the form to return.
     *
     * @return array            The form data.
     * @throws Horde_Exception_PermissionDenied
     * @throws Horde_Exception_NotFound
     * @throws Ulaform_Exception
     */
    public function getForm($form_id, $permission = Horde_Perms::SHOW)
    {
        /* Check permissions */
        if (!parent::hasPermission($permission, $form_id)) {
            throw new Horde_Exception_PermissionDenied(_("You don't have the right permission to access this form."));
        }

        /* Get the main form data. */
        $sql = 'SELECT form_id, user_uid, form_name, form_action, form_params,'
                . ' form_onsubmit FROM ulaform_forms WHERE form_id = ?';
        try {
            $form = $this->_db->selectOne($sql, array((int)$form_id));
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        /* Check if the form exists. */
        if (empty($form)) {
            throw new Horde_Exception_NotFound(sprintf(_("No such form ID \"%s\"."), $form_id));
        }

        /* Unserialize the form params. */
        $form['form_params'] = Horde_Serialize::unserialize($form['form_params'], Horde_Serialize::UTF7_BASIC);

        return $form;
    }

    /**
     * Fetches the fields for a particular form.
     *
     * @param integer $form_id  The form id of the form to return.
     *
     * @return array  The fields.
     * @throws Ulaform_Exception
     */
    public function getFields($form_id, $field_id = null)
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

        try {
            $results = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e);
        }

        $fields = array();
        foreach ($results as $field) {
            /* If no internal name set, generate one using field_id. */
            if (empty($field['field_name'])) {
                $field['field_name'] = 'field_' . $field['field_id'];
            }

            /* Check if any params and unserialize, otherwise return null. */
            if (!empty($field['field_params'])) {
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
     * @return boolean  True on success.
     * @throws Ulaform_Exception
     */
    public function deleteForm($form_id)
    {
        /* Delete the form. */
        $sql = 'DELETE FROM ulaform_forms WHERE form_id = ?';
        try {
            $this->_db->execute($sql, array((int)$form_id));
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        /* Delete the fields for this form. */
        $sql = 'DELETE FROM ulaform_fields WHERE form_id = ?';
        try {
            $this->_db->execute($sql, array((int)$form_id));
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Deletes a field from the database.
     *
     * @param integer $field_id  The field id of the field to delete.
     *
     * @return boolean  True on success.
     * @throws Ulaform_Exception
     */
    public function deleteField($field_id)
    {
        /* Delete the field. */
        $sql = 'DELETE FROM ulaform_fields WHERE field_id = ?';
        try {
            $this->_db->execute($sql, array((int)$field_id));
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Gets the next field order position within a form.
     *
     * @param integer  $form_id
     *
     * @return integer
     * @throws Ulaform_Exception
     */
    protected function _nextFieldOrder($form_id)
    {
        $sql = 'SELECT MAX(field_order) FROM ulaform_fields WHERE form_id = ?';
        try {
            return $this->_db->selectValue($sql, array($form_id)) + 1;
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage);
        }
    }

}
