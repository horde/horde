<?php
/**
 * Provides methods to retrieve free/busy data for resources.
 *
 * PHP version 5
 * 
 * @todo Merge this class with Kolab_FreeBusy and Kronolith_FreeBusy into a
 *       single Horde_Freebusy handler.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Retrieves free/busy data for an email address.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL>=2.1). If you
 * did not receive this file,
 * see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Freebusy
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Class parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params A hash containing any additional configuration or
     *                      connection parameters a subclass might need.
     */
    protected function __construct($params)
    {
        $this->_params = $params;
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Resource_Getfreebusy instance
     * based on $driver.
     *
     * @param mixed $driver The type of concrete
     *                      Horde_Kolab_Resource_Getfreebusy subclass to
     *                      return.
     * @param array $params A hash containing any additional configuration or
     *                      connection parameters a subclass might need.
     *
     * @return Horde_Kolab_Resource_Getfreebusy The newly created concrete
     *                                          Horde_Kolab_Resource_Getfreebusy
     *                                          instance, or false an error.
     */
    static public function factory($driver, $params = array())
    {
        $driver = ucfirst(basename($driver));
        $class  = ($driver == 'None')
            ? 'Horde_Kolab_Resource_Freebusy'
            : 'Horde_Kolab_Resource_Freebusy_' . $driver;

        require_once dirname(__FILE__) . '/Freebusy/' . $driver . '.php';

        if (!class_exists($class)) {
            $class = 'Horde_Kolab_Resource_Freebusy';
        }

        return new $class($params);
    }

    /**
     * Attempts to return a reference to a concrete
     * Horde_Kolab_Resource_Getfreebusy instance based on $driver.
     *
     * It will only create a new instance if no Horde_Kolab_Resource_Getfreebusy
     * instance with the same parameters currently exists.
     *
     * This method must be invoked as:
     * <code>$var = Horde_Kolab_Resource_Getfreebusy::singleton();</code>
     *
     * @param mixed $driver The type of concrete
     *                      Horde_Kolab_Resource_Getfreebusy subclass to
     *                      return.
     * @param array $params A hash containing any additional configuration or
     *                      connection parameters a subclass might need.
     *
     * @return Horde_Token The concrete Horde_Kolab_Resource_Getfreebusy
     *                      reference, or false on error.
     */
    static public function singleton($driver = null, $params = array())
    {
        global $conf;

        if (isset($GLOBALS['KOLAB_FILTER_TESTING'])) {
            $driver = 'mock';
            $params['data'] = $GLOBALS['KOLAB_FILTER_TESTING'];
        }

        if (empty($driver)) {
            $driver = $conf['freebusy']['driver'];
        }

        ksort($params);
        $sig = hash('md5', serialize(array($driver, $params)));

        if (!isset(self::$_instances[$sig])) {
            self::$_instances[$sig] = Horde_Kolab_Resource_Freebusy::factory($driver,
                                                                             $params);
        }

        return self::$_instances[$sig];
    }

    /**
     * Retrieve Free/Busy URL for the specified resource id.
     *
     * @param string $resource The id of the resource (usually a mail address).
     *
     * @return string The Free/Busy URL for that resource.
     */
    protected function getUrl($resource)
    {
        return '';
    }

    /**
     * Retrieve Free/Busy data for the specified resource.
     *
     * @param string $resource Fetch the Free/Busy data for this resource
     *                         (usually a mail address).
     *
     * @return Horde_iCalendar_vfreebusy The Free/Busy data.
     */
    public function get($resource)
    {
        /* Return an empty VFB object. */
        $vCal = new Horde_iCalendar();
        $vFb = Horde_iCalendar::newComponent('vfreebusy', $vCal);
        $vFb->setAttribute('ORGANIZER', $resource);

        return $vFb;

    }
}