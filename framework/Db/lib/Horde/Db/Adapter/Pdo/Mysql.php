<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Db_Adapter_Pdo_Mysql extends Horde_Db_Adapter_Pdo_Abstract
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

        if (isset($this->_config['port'])) {
            if (empty($this->_config['host'])) {
                $msg = 'host is required if port is specified';
                throw new Horde_Db_Exception($msg);
            }

            if (preg_match('/[^\d\.]/', $this->_config['host'])) {
                $msg = 'pdo_mysql ignores port unless IP address is used for host';
                throw new Horde_Db_Exception($msg);
            }
        }

        return parent::_parseConfig();
    }

}
