<?php
/**
 * Handles a share parameters.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Handles a share parameters.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Query_Share_Base
implements Horde_Kolab_Storage_List_Query_Share
{
    /** The folder description */
    const ANNOTATION_DESCRIPTION = '/shared/comment';

    /** The share parameters */
    const ANNOTATION_SHARE_PARAMETERS = '/shared/vendor/horde/share-params';

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The queriable list.
     * @param array                    $params Additional parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        $params
    ) {
        $this->_driver = $list->getDriver();
    }

    /**
     * Returns the share description.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share description.
     */
    public function getDescription($folder)
    {
        return $this->_driver->getAnnotation(
            $folder, self::ANNOTATION_DESCRIPTION
        );
    }

    /**
     * Returns the share parameters.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share parameters.
     */
    public function getParameters($folder)
    {
        $parameters = $this->_driver->getAnnotation(
                $folder, self::ANNOTATION_SHARE_PARAMETERS
        );
        if (!empty($parameters)) {
            return unserialize(base64_decode($parameters));
        } else {
            return array();
        }
    }

    /**
     * Set the share description.
     *
     * @param string $folder      The folder name.
     * @param string $description The share description.
     *
     * @return NULL
     */
    public function setDescription($folder, $description)
    {
        $this->_driver->setAnnotation(
            $folder, self::ANNOTATION_DESCRIPTION, $description
        );
    }

    /**
     * Set the share parameters.
     *
     * @param string $folder     The folder name.
     * @param array  $parameters The share parameters.
     *
     * @return string The encoded share parameters.
     */
    public function setParameters($folder, array $parameters)
    {
        $this->_driver->setAnnotation(
            $folder,
            self::ANNOTATION_SHARE_PARAMETERS,
            base64_encode(serialize($parameters))
        );
    }

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function createFolder($folder, $type = null)
    {
    }

    /**
     * Delete a folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
    }

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function renameFolder($old, $new)
    {
    }

    /**
     * Synchronize the ACL information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
    }
}