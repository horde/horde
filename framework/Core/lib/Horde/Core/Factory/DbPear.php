<?php
/**
 * A Horde_Injector:: based factory for creating PEAR DB objects..
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating PEAR DB objects..
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_DbPear
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the DB instance.
     *
     * @param string $type  Either 'read' or 'rw'.
     *
     * @return DB  The singleton DB instance.
     * @throws Horde_Exception
     */
    public function getOb($type = 'rw')
    {
        if (isset($this->_instances[$type])) {
            return $this->_instances[$type];
        }

        $params = array_merge(array(
            'database' => '',
            'hostspec' => '',
            'password' => '',
            'username' => ''
        ), $GLOBALS['conf']['sql']);
        if ($type == 'read') {
            $params = array_merge($params, $params['read']);
        }

        Horde::assertDriverConfig($params, 'sql', array('charset', 'phptype'), 'SQL');

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

        $this->_instances[$type] = $db;

        return $db;
    }

}
