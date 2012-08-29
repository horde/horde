<?php
/**
 * The interface of the basic list query.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The interface of the basic list query.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_List_Query_List
{
    /** The folder type annotation */
    const ANNOTATION_FOLDER_TYPE = '/shared/vendor/kolab/folder-type';

    /**
     * Returns the folder types as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type as values.
     */
    abstract public function listTypes();

    /**
     * List basic folder data for the folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    abstract public function dataByType($type);

    /**
     * List basic folder data for the specified folder.
     *
     * @param string $folder The folder path.
     *
     * @return array The folder data.
     */
    abstract public function folderData($folder);

    /**
     * List all folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    abstract public function listByType($type);

    /**
     * Get the folder owners.
     *
     * @return array The folder owners with the folder names as key and the
     *               owner as values.
     */
    abstract public function listOwners();

    /**
     * Return the list of default folders.
     *
     * @return array An array with owners as keys and another array as
     *               value. The second array associates type (key) with the
     *               corresponding default folder (value).
     */
    abstract public function listDefaults();

    /**
     * Get the default folder for a certain type.
     *
     * @param string $type The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    abstract public function getDefault($type);

    /**
     * Get the default folder for a certain type from a different owner.
     *
     * @param string $owner The folder owner.
     * @param string $type  The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    abstract public function getForeignDefault($owner, $type);

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    abstract public function getStamp();
}