<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Common
{
    /* Utility function to use until code is transferred to new DB code. */
    public function createDb($params, $ident)
    {
        Horde::assertDriverConfig($params, 'sql', array('charset', 'phptype'), $ident);

        $params = array_merge(array(
            'database' => '',
            'hostspec' => '',
            'password' => '',
            'username' => ''
        ), $params);

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
