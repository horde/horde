<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/*
I have been exploring ZF to implement for my client and found a few
interesting things that I thought might be of interest to others using
the Oracle oci8 driver. While not really "broken", the support for the
Oracle oci8 driver does appear to be missing a few useful / convenient
features:

The Zend_Db_Oracle driver ignores the 'host' parameter. This is only
an issue when PHP is compiled with Oracles instant client which need
not use TNS. For instance the following $dsn throws the following
exception "TNS:could not resolve the connect identifier specified"

Fails:

$dsn = array(
    'host'      =>'myhost',
    'username'  => 'zend',
    'password'  => 'zend',
    'dbname'    => 'xe',
    'options'   => $options
);

This is not surprising since the instant client does not use TNS as
stated. To get this to work you need to prepend the 'dbname' parameter
with the host name, which although easy to do is not very intuitive.

Works:

$dsn = array(
    'host' => 'totally ignored',
    'username'  => 'zend',
    'password'  => 'zend',
    'dbname'    => 'myhost/xe',
    'options'   => $options
);

Zend_Db_Statement_Oracle

This class states that it does not support case folding due to a
limitation of the Oci8 driver. The driver may not support case
folding, but after a quick review of the code I was curious as to why
this was not emulated, at least for results being returned as an
associative array (objects would not be much of a stretch either)?
This would be a great feature to have and save those looking for this
feature from having to call the array_change_key_case() function on
every row returned J
*/

/**
 * OCI PDO Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Pdo_Oci extends Horde_Db_Adapter_Pdo_Base
{
    /**
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Oracle_Schema';

    /**
     * Get the appropriate DML object and call the parent constructor.
     *
     * @param array $options Connection options.
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    /**
     * @return  string
     */
    public function adapterName()
    {
        return 'PDO_Oci';
    }

    /**
     * Get a description of the database table that $model is going to
     * reflect.
     */
    public function loadModel($model)
    {
        $table = $this->dml->quoteColumnName(strtoupper($model->table));
        $tblinfo = $this->select('SELECT column_name, data_type, data_length, nullable, data_default FROM all_tab_columns WHERE table_name = '
                                 . $table);
        while ($col = $tblinfo->fetch()) {
            $model->addField($col['column_name'], array('type' => $col['data_type'],
                                                        'null' => ($col['nullable'] != 'N'),
                                                        'default' => $col['data_default'],
                                                        'length' => $col['data_length']));
        }

        // Only fetch the first primary key for now.
        $model->key = $this->selectOne('SELECT DISTINCT b.column_name FROM all_constraints a, all_cons_columns b WHERE a.table_name = '
                                       . $table . ' AND a.constraint_type = \'P\' AND b.constraint_name = a.constraint_name');
    }

    /**
     */
    protected function _lastInsertId($sequence)
    {
        $data = $this->selectOne('SELECT ' . $this->dml->quoteColumnName($sequence) . '.currval FROM dual');
    }

    /**
     */
    public function getTables()
    {
        return 'SELECT table_name FROM all_tables';
    }

    /**
     */
    protected function _limit($query, &$sql, &$bindParams)
    {
        if ($query->limit) {

            $sql = 'SELECT q2.* FROM (SELECT rownum r, q1.* FROM (' . $sql . ') q1) q2
                    WHERE r BETWEEN ' . $query->limitOffset . ' AND ' . ($query->limit + $query->limitOffset);
        }
    }

}
