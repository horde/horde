<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * PDO_MySQL Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
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
        $this->_checkRequiredConfig();

        // collect options to build PDO Data Source Name (DSN) string
        $dsnOpts = $this->_config;
        unset(
            $dsnOpts['adapter'],
            $dsnOpts['username'],
            $dsnOpts['password']
        );
        $dsnOpts = $this->_normalizeConfig($dsnOpts);

        if (isset($dsnOpts['port'])) {
            if (empty($dsnOpts['host'])) {
                $msg = 'host is required if port is specified';
                throw new Horde_Db_Exception($msg);
            }

            if ($dsnOpts['host'] == 'localhost') {
                $msg = 'pdo_mysql ignores port if using "localhost" for host';
                throw new Horde_Db_Exception($msg);
            }
        }

        // return DSN and user/pass for connection
        return array(
            $this->_buildDsnString($dsnOpts),
            $this->_config['username'],
            $this->_config['password']);
    }
}
