<?php
/**
 * Ulaform_Action_Sql Class provides a Ulaform action driver to submit the
 * results of a form to database.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Vilius Šumskas <vilius@lnk.lt>
 * @package Ulaform
 */
class Ulaform_Action_Sql extends Ulaform_Action {

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
     * Actually carry out the action.
     *
     * @return boolean  True on success.
     * @throws Ulaform_Exception
     */
    public function doAction($form_params, $form_data, $fields)
    {
        /* Check if table exists. */
        if (!in_array($form_params['table'], $this->_db->tables())) {
            try {
                $this->_createDataTable($form_params, $fields);
            } catch (Horde_Db_Exception $e) {
                throw new Ulaform_Exception($e->getMessage());
            }
        }

        /* Submit data to database. */
        $columns = array();
        $values = array();
        foreach ($fields as $field) {
            switch ($field['field_type']) {
            case 'file':
            case 'image':
                if (count($form_data[$field['field_name']])) {
                    $data = file_get_contents($form_data[$field['field_name']]['file']);
                    if (Horde_String::lower($this->_db->adapterName()) == 'pgsql') {
                        $data = bin2hex($data);
                    }
                    $columns[] = $field['field_name'];
                    $values[] = $data;
                }
                break;

            case 'set':
                $columns[] = $field['field_name'];
                $values[] = implode(', ', $form_data[$field['field_name']]);
                break;

            default:
                $data = $form_data[$field['field_name']];
                $columns[] = $field['field_name'];
                $values[] = Horde_String::convertCharset($data, 'UTF-8', $this->_charset);
                break;
            }
        }
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                       $form_params['table'],
                       implode(', ', $columns),
                       str_repeat('?, ', count($values) - 1) . '?');

        try {
            $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Identifies this action driver and returns a brief description, used by
     * admin when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    static public function getInfo()
    {
        $info['name'] = _("SQL");
        $info['desc'] = _("This driver allows to insertion of form results into a database.");

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
        $params['table'] = array('label' => _("Table"), 'type' => 'text');

        return $params;
    }

    /**
     * Create table for submiting data.
     *
     * @return boolean  True on success.
     * @throws Ulaform_Exception
     */
    protected function _createDataTable($form_params, $fields)
    {
        /* Generate SQL query. */
        $columns = array();
        foreach ($fields as $field) {
            switch ($field['field_type']) {
            case 'file':
            case 'image':
                // TODO: Use Horde_SQL
                switch (Horde_String::lower($this->_db->adapterName())) {
                case 'pgsql':
                    $columns[] = $field['field_name'] . ' TEXT';
                    break;

                case 'mysql':
                case 'mysqli':
                    $columns[] = $field['field_name'] . ' MEDIUMBLOB';
                    break;

                default:
                    $columns[] = $field['field_name'] . ' BLOB';
                    break;
                }
                break;
            case 'address':
            case 'countedtext':
            case 'description':
            case 'html':
            case 'longtext':
            case 'set':
                $columns[] = $field['field_name'] . ' TEXT';
                break;
            default:
                $columns[] = $field['field_name'] . ' VARCHAR(255)';
                break;
            }
        }
        $sql = sprintf('CREATE TABLE %s (%s)',
                       $form_params['table'],
                       implode(', ', $columns));

        /* Create table. */
        try {
            $this->_db->execute($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Ulaform_Exception($e);
        }
        return true;
    }

}
