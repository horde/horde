<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
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
     * Adds an ID map to the backend storage.
     *
     * @param string $internal  An internal object ID.
     * @param string $external  An external object ID.
     * @param string $path      The path to an object.
     */
    abstract public function addMap($internal, $external, $path);

    /**
     * Returns an internal ID from a stored ID map.
     *
     * @param string $external  An external object ID.
     * @param string $path      The path to an object.
     *
     * @return string  The object's internal ID or null.
     */
    abstract public function getInternalId($external, $path);

    /**
     * Returns an external ID from a stored ID map.
     *
     * @param string $internal  An internal object ID.
     * @param string $path      The path to an object.
     *
     * @return string  The object's internal ID or null.
     */
    abstract public function getExternalId($internal, $path);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $internal  An internal object ID.
     * @param string $path      The path to an object.
     */
    abstract public function deleteInternalId($internal, $path);

    /**
     * Deletes an ID map from the backend storage.
     *
     * @param string $external  An external object ID.
     * @param string $path      The path to an object.
     */
    abstract public function deleteExternalId($external, $path);
}
