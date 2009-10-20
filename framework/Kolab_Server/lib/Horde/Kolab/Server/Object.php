<?php
/**
 * Interface describing Kolab objects stored in the server database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Interface describing Kolab objects stored in the server database.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
interface Horde_Kolab_Server_Object
{
    /**
     * Get the GUID of this object
     *
     * @return string the GUID of this object
     */
    public function getGuid();

    /**
     * Get the external attributes supported by this object.
     *
     * @return array The external attributes supported by this object. This is a
     * list of abbreviated attribute class names.
     */
    public function getExternalAttributes();

    /**
     * Get the internal attributes supported by this object.
     *
     * @return array The internal attributes supported by this object. This is
     * an association of internal attribute names an the correspodning attribute
     * class names.
     */
    public function getInternalAttributes();

    /**
     * Does the object exist?
     *
     * @return boolean True if the object exists, false otherwise.
     */
    public function exists();

    /**
     * Read the object into the cache
     *
     * @return array The read data.
     */
    public function readInternal();

    /**
     * Get the specified attribute of this object
     *
     * @param string $attr The attribute to read
     *
     * @return array The value(s) of this attribute
     */
    public function getInternal($attr);

    /**
     * Get the specified attribute of this object.
     *
     * @param string $attr The attribute to read.
     *
     * @return mixed The value of this attribute.
     */
    public function getExternal($attr);

    /**
     * Saves object information. This may either create a new entry or modify an
     * existing entry.
     *
     * Please note that fields with multiple allowed values require the callee
     * to provide the full set of values for the field. Any old values that are
     * not resubmitted will be considered to be deleted.
     *
     * @param array $info The information about the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If saving the data failed.
     */
    public function save(array $info);

    /**
     * Delete this object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If deleting the object failed.
     */
    public function delete();

    /**
     * Returns the set of actions supported by this object type.
     *
     * @return array An array of supported actions.
     */
    public function getActions();

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @return string The ID.
     */
    public function generateId(array &$info);

    /**
     * Distill the server side object information to save.
     *
     * @param array &$info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info);

}
