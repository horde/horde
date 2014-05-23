<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * Implements an SQL based storage backend.
 *
 * This is not for DAV content storage, but for metadata storage.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Storage_Sql extends Horde_Dav_Storage_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        if (!isset($params['db'])) {
            throw new Horde_Dav_Exception('The \'db\' parameter is missing.');
        }
        $this->_db = $params['db'];
    }

    /**
     * Adds an object ID map to the backend storage.
     *
     * @param string $internal    An internal object ID.
     * @param string $external    An external object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    public function addObjectMap($internal, $external, $collection)
    {
        try {
            $this->_db->insert(
                'INSERT INTO horde_dav_objects (id_internal, id_external, id_collection) '
                . 'VALUES (?, ?, ?)',
                array($internal, $external, $collection)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Adds a collection ID map to the backend storage.
     *
     * @param string $internal   An internal collection ID.
     * @param string $external   An external collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    public function addCollectionMap($internal, $external, $interface)
    {
        try {
            $this->_db->insert(
                'INSERT INTO horde_dav_collections (id_internal, id_external, id_interface) '
                . 'VALUES (?, ?, ?)',
                array($internal, $external, $interface)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Returns an internal ID from a stored object ID map.
     *
     * @param string $external    An external object ID.
     * @param string $collection  The collection of an object.
     *
     * @return string  The object's internal ID or null.
     *
     * @throws Horde_Dav_Exception
     */
    public function getInternalObjectId($external, $collection)
    {
        try {
            return $this->_db->selectValue(
                'SELECT id_internal FROM horde_dav_objects '
                . 'WHERE id_external = ? AND id_collection = ?',
                array($external, $collection)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Returns an external ID from a stored object ID map.
     *
     * @param string $internal    An internal object ID.
     * @param string $collection  The collection of an object.
     *
     * @return string  The object's external ID or null.
     *
     * @throws Horde_Dav_Exception
     */
    public function getExternalObjectId($internal, $collection)
    {
        try {
            return $this->_db->selectValue(
                'SELECT id_external FROM horde_dav_objects '
                . 'WHERE id_internal = ? AND id_collection = ?',
                array($internal, $collection)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Returns an internal ID from a stored collection ID map.
     *
     * @param string $external   An external collection ID.
     * @param string $interface  The collection's application.
     *
     * @return string  The collection's internal ID or null.
     *
     * @throws Horde_Dav_Exception
     */
    public function getInternalCollectionId($external, $interface)
    {
        try {
            return $this->_db->selectValue(
                'SELECT id_internal FROM horde_dav_collections '
                . 'WHERE id_external = ? AND id_interface = ?',
                array($external, $interface)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Returns an external ID from a stored collection ID map.
     *
     * @param string $internal   An internal collection ID.
     * @param string $interface  The collection's application.
     *
     * @return string  The collection's external ID.
     *
     * @throws Horde_Dav_Exception
     */
    public function getExternalCollectionId($internal, $interface)
    {
        try {
            $external = $this->_db->selectValue(
                'SELECT id_external FROM horde_dav_collections '
                . 'WHERE id_internal = ? AND id_interface = ?',
                array($internal, $interface)
            );
            if (!$external) {
                $external = $interface . '~' . $internal;
                $this->addCollectionMap($internal, $external, $interface);
            }
            return $external;
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Returns an interface name from a stored collection ID map.
     *
     * @param string $external   An external collection ID.
     *
     * @return string  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    public function getCollectionInterface($external)
    {
        try {
            return $this->_db->selectValue(
                'SELECT id_interface FROM horde_dav_collections '
                . 'WHERE id_external = ?',
                array($external)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $internal    An internal object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    public function deleteInternalObjectId($internal, $collection)
    {
        try {
            $this->_db->delete(
                'DELETE FROM horde_dav_objects '
                . 'WHERE id_internal = ? AND id_collection = ?',
                array($internal, $collection)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $external    An external object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    public function deleteExternalObjectId($external, $collection)
    {
        try {
            $this->_db->delete(
                'DELETE FROM horde_dav_objects '
                . 'WHERE id_external = ? AND id_collection = ?',
                array($external, $collection)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $internal    An internal collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    public function deleteInternalCollectionId($internal, $interface)
    {
        try {
            $this->_db->delete(
                'DELETE FROM horde_dav_collections '
                . 'WHERE id_internal = ? AND id_interface = ?',
                array($internal, $interface)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $external    An external collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    public function deleteExternalCollectionId($external, $interface)
    {
        try {
            $this->_db->delete(
                'DELETE FROM horde_dav_collections '
                . 'WHERE id_external = ? AND id_interface = ?',
                array($external, $interface)
            );
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }
    }
}
