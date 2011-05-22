<?php
/**
 * Ulaform_Action_sql Class provides a Ulaform action driver to submit the
 * results of a form to database.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/Action/sql.php,v 1.27 2009-07-09 08:18:44 slusarz Exp $
 *
 * @author  Vilius Sumskas <vilius@lnk.lt>
 * @package Ulaform
 */
class Ulaform_Action_sql extends Ulaform_Action {

    var $_db;

    /**
     * Actually carry out the action.
     *
     * @return mixed True or PEAR Error.
     */
    function doAction($form_params, $form_data, $fields)
    {
        /*  Connect to database. */
        $this->initialise();

        /* Check if table exists. */
        if (!in_array($form_params['table'], $this->_db->getListOf('tables'))) {
            if (is_a($result = $this->createDataTable($form_params, $fields), 'PEAR_Error')) {
                return $result;
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
                    if ($this->_db->dbsyntax == 'mssql' ||
                        $this->_db->dbsyntax == 'pgsql') {
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
                $values[] = Horde_String::convertCharset($data, Horde_Nls::getCharset(), $this->_params['charset']);
                break;
            }
        }
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                       $form_params['table'],
                       implode(', ', $columns),
                       str_repeat('?, ', count($values) - 1) . '?');

        Horde::logMessage('SQL Query by Ulaform_Action_sql::doAction(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        } else {
            return true;
        }
    }

    /**
     * Identifies this action driver and returns a brief description, used by
     * admin when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getInfo()
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
    function getParams()
    {
        $params = array();
        $params['table'] = array('label' => _("Table"), 'type' => 'text');

        return $params;
    }

    /**
     * Create table for submiting data.
     *
     * @return mixed True or PEAR Error.
     */
    function createDataTable($form_params, $fields)
    {
        /* Generate SQL query. */
        $columns = array();
        foreach ($fields as $field) {
            switch ($field['field_type']) {
            case 'file':
            case 'image':
                // TODO: Use Horde_SQL
                switch ($this->_db->dbsyntax) {
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
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        } else {
            return true;
        }
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
