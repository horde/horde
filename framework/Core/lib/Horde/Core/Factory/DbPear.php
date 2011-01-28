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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
     * @param string $app   The application.
     * @param mixed $dtype  The type. If this is an array, this is used as
     *                      the configuration array.
     *
     * @return DB  The singleton DB instance.
     * @throws Horde_Exception
     */
    public function create($type = 'rw', $app = 'horde', $dtype = null)
    {
        global $registry;

        $sig = hash('md5', serialize($type . '|' . $app . '|' . $dtype));

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $pushed = ($app == 'horde')
            ? false
            : $registry->pushApp($app);

        $config = is_array($dtype)
            ? $dtype
            : $this->getConfig($dtype);

        if ($type == 'read' && !empty($config['splitread'])) {
            $config = array_merge($config, $config['read']);
        }

        Horde::assertDriverConfig($config, 'sql', array('charset', 'phptype'));

        /* Connect to the SQL server using the supplied parameters. */
        $db = DB::connect($config, array(
            'persistent' => !empty($config['persistent']),
            'ssl' => !empty($config['ssl'])
        ));

        if ($db instanceof PEAR_Error) {
            if ($pushed) {
                $registry->popApp();
            }
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

        if ($pushed) {
            $registry->popApp();
        }

        $this->_instances[$sig] = $db;

        return $db;
    }

    /**
     */
    public function getConfig($type)
    {
        return Horde::getDriverConfig($type, 'sql');
    }

}
