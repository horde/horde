<?php
/**
 * Hylax_Storage Class
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Hylax
 */
class Hylax_Storage {

    /**
     * A hash containing any parameters for the current storage driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * @var VFS
     */
    var $_vfs;

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this storage driver.
     * @throws VFS_Exception
     */
    function Hylax_Storage($params)
    {
        global $conf;

        $this->_params = $params;

        /* Set up the VFS storage. */
        if (!isset($conf['vfs']['type'])) {
            Horde::fatal(_("You must configure a VFS backend to use Hylax."), __FILE__, __LINE__);
        }
        $vfs_driver = $conf['vfs']['type'];
        $vfs_params = Horde::getDriverConfig('vfs', $vfs_driver);
        $this->_vfs = VFS::singleton($vfs_driver, $vfs_params);
    }

    function saveFaxData($data, $type = '.ps')
    {
        /* Get the next ID. */
        $fax_id = $this->newFaxId();
        if (is_a($fax_id, 'PEAR_Error')) {
            return $fax_id;
        }

        /* Save data to VFS backend. */
        $path = Hylax::getVFSPath($fax_id);
        $file = $fax_id . $type;
        try {
            $this->_vfs->writeData($path, $file, $data, true);
        } catch (VFS_Exception $e) {
            Horde::logMessage('Could not save fax file to VFS: ' . $e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            throw $e;
        }
        return $fax_id;
    }

    function createFax($info, $fix_owner = false)
    {
        /* In case this is just a fax creation without yet a number assigned
         * create an empty number. */
        if (!isset($info['fax_number'])) {
            $info['fax_number'] = '';
        }

        /* Set the folder. */
        $info['fax_folder'] = ($info['fax_type']) ? 'outbox' : 'inbox';

        /* Set timestamp. */
        if (empty($info['fax_created'])) {
            $info['fax_created'] = time();
        }

        $data = $this->getFaxData($info['fax_id']);
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage('Could not get fax data: ' . $data->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return $data;
        }

        /* Create a fax image object. */
        require_once HYLAX_BASE . '/lib/Image.php';
        $image = new Hylax_Image();
        $image->loadData($data);
        if (empty($info['fax_pages'])) {
            $info['fax_pages'] = $image->getNumPages();
        }

        /* Save to backend. */
        $result = $this->_createFax($info);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        global $conf;
        foreach ($conf['fax']['notify'] as $rec) {
            mail($rec, _("New fax from: ") . $info['fax_number'], '',
                 'From: '. $conf['fax']['notifyfrom']);
        }
        return true;
    }

    function getFaxData($fax_id)
    {
        $path = Hylax::getVFSPath($fax_id);
        $file = $fax_id . '.ps';
        try {
            return $this->_vfs->read($path, $file);
        } catch (VFS_Exception $e) {
            Horde::logMessage(sprintf("%s '%s/%s'.", $e->getMessage(), $path, $file), __FILE__, __LINE__, PEAR_LOG_ERR);
            throw $e;
        }
    }

    function listFaxes($folder)
    {
        return $this->_listFaxes($folder);
    }

    function send($fax_id, $number)
    {
        global $hylax;

        $this->_setFaxNumber($fax_id, $number);

        $data = $this->getFaxData($fax_id);

        $job_id = $hylax->gateway->send($number, $data);

        $this->_setJobId($fax_id, $job_id);
    }

    /**
     * Attempts to return a concrete Hylax_Storage instance based on $driver.
     *
     * @param string $driver  The type of concrete Hylax_Storage subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Hylax_Storage  The newly created concrete Hylax_Storage
     *                        instance, or false on error.
     */
    function &factory($driver, $params = array())
    {
        $driver = basename($driver);
        include_once dirname(__FILE__) . '/Storage/' . $driver . '.php';
        $class = 'Hylax_Storage_' . $driver;
        if (class_exists($class)) {
            $storage = new $class($params);
            return $storage;
        } else {
            Horde::fatal(PEAR::raiseError(sprintf(_("No such backend \"%s\" found"), $driver)), __FILE__, __LINE__);
        }
    }

    /**
     * Attempts to return a reference to a concrete Hylax_Storage instance
     * based on $driver.
     *
     * It will only create a new instance if no Hylax_Storage instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Hylax_Storage::singleton()
     *
     * @param string $driver  The type of concrete Hylax_Storage subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Hylax_Storage instance, or false on
     *                error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Hylax_Storage::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
