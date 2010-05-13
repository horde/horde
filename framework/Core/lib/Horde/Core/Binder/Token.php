<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Token implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = isset($GLOBALS['conf']['token'])
            ? $GLOBALS['conf']['token']['driver']
            : 'Null';
        $params = isset($GLOBALS['conf']['token'])
            ? Horde::getDriverConfig('token', $GLOBALS['conf']['token']['driver'])
            : array();

        if (strcasecmp($driver, 'Sql') === 0) {
            Horde_Util::assertDriverConfig($params, array('phptype'), 'token SQL');

            $params = array_merge(array(
                'database' => '',
                'hostspec' => '',
                'password' => '',
                'table' => 'horde_tokens',
                'username' => ''
            ), $params);

            /* Connect to the SQL server using the supplied parameters. */
            $write_db = $this->_createDb($params);

            /* Check if we need to set up the read DB connection
             * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = $this->_createDb(array_merge($params, $params['read']));
            }
        } elseif (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Token::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

    protected function _createDb($params)
    {
        /* Connect to the SQL server using the supplied parameters. */
        $db = DB::connect($params, array(
            'persistent' => !empty($params['persistent']),
            'ssl' => !empty($params['ssl'])
        ));

        if ($db instanceof PEAR_Error) {
            throw new Horde_Exception($db);
        }

        // Set DB portability options.
        switch ($db->phptype) {
        case 'mssql':
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;

        default:
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            break;
        }

        return $db;
    }

}
