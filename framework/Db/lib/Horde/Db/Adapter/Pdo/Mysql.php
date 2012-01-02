<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * PDO_MySQL Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Pdo_Mysql extends Horde_Db_Adapter_Pdo_Base
{
    /**
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Mysql_Schema';

    /**
     * @return  string
     */
    public function adapterName()
    {
        return 'PDO_MySQL';
    }

    /**
     * @return  boolean
     */
    public function supportsMigrations()
    {
        return true;
    }


    /*##########################################################################
    # Connection Management
    ##########################################################################*/

    /**
     * Connect to the db
     */
    public function connect()
    {
        if ($this->_active) {
            return;
        }

        parent::connect();

        // ? $this->_connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        // Set the default charset. http://dev.mysql.com/doc/refman/5.1/en/charset-connection.html
        if (!empty($this->_config['charset'])) {
            $this->setCharset($this->_config['charset']);
        }
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Parse configuration array into options for PDO constructor.
     *
     * http://pecl.php.net/bugs/7234
     * Setting a bogus socket does not appear to work.
     *
     * @throws  Horde_Db_Exception
     * @return  array  [dsn, username, password]
     */
    protected function _parseConfig()
    {
        $this->_config['adapter'] = 'mysql';

        $this->_checkRequiredConfig(array('adapter', 'username'));

        if (!empty($this->_config['socket'])) {
            $this->_config['unix_socket'] = $this->_config['socket'];
            unset($this->_config['socket']);
        }

        if (!empty($this->_config['host']) &&
            $this->_config['host'] == 'localhost') {
            $this->_config['host'] = '127.0.0.1';
        }

        // Try an empty password if it's not set.
        if (!isset($this->_config['password'])) {
            $this->_config['password'] = '';
        }

        // Collect options to build PDO Data Source Name (DSN) string.
        $dsnOpts = $this->_config;
        unset($dsnOpts['adapter'],
              $dsnOpts['username'],
              $dsnOpts['password'],
              $dsnOpts['charset'],
              $dsnOpts['phptype']);
        $dsnOpts = $this->_normalizeConfig($dsnOpts);

        if (isset($dsnOpts['port'])) {
            if (empty($dsnOpts['host'])) {
                throw new Horde_Db_Exception('Host is required if port is specified');
            }
        }

        if (isset($dsnOpts['unix_socket'])) {
            if (!empty($dsnOpts['host']) ||
                !empty($dsnOpts['port'])) {
                throw new Horde_Db_Exception('Host and port must not be set if using a UNIX socket');
            }
        }

        // Return DSN and user/pass for connection.
        return array(
            $this->_buildDsnString($dsnOpts),
            $this->_config['username'],
            $this->_config['password']);
    }
}
