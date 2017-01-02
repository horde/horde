<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
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
 * Base class for storage backends.
 *
 * This is not for DAV content storage, but for metadata storage.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
abstract class Horde_Dav_Storage_Base
{
    /**
     * Adds an object ID map to the backend storage.
     *
     * @param string $internal    An internal object ID.
     * @param string $external    An external object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function addObjectMap($internal, $external, $collection);

    /**
     * Adds a collection ID map to the backend storage.
     *
     * @param string $internal   An internal collection ID.
     * @param string $external   An external collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function addCollectionMap($internal, $external, $interface);

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
    abstract public function getInternalObjectId($external, $collection);

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
    abstract public function getExternalObjectId($internal, $collection);

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
    abstract public function getInternalCollectionId($external, $interface);

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
    abstract public function getExternalCollectionId($internal, $interface);

    /**
     * Returns an interface name from a stored collection ID map.
     *
     * @param string $external   An external collection ID.
     *
     * @return string  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function getCollectionInterface($external);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $internal    An internal object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function deleteInternalObjectId($internal, $collection);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $external    An external object ID.
     * @param string $collection  The collection of an object.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function deleteExternalObjectId($external, $collection);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $internal    An internal collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function deleteInternalCollectionId($internal, $interface);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $external    An external collection ID.
     * @param string $interface  The collection's application.
     *
     * @throws Horde_Dav_Exception
     */
    abstract public function deleteExternalCollectionId($external, $interface);
}
