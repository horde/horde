<?php
/**
 * The basic list query.
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
 * The basic list query.
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
class Horde_Kolab_Storage_List_Query_List_Base
extends Horde_Kolab_Storage_List_Query_List
{
    /**
     * The IMAP driver to query the backend.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for folder types.
     *
     * @var Horde_Kolab_Storage_Folder_Types
     */
    private $_folder_types;

    /**
     * Handles default folders.
     *
     * @var Horde_Kolab_Storage_List_Query_List_Defaults
     */
    private $_defaults;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The driver to access the backend.
     * @param Horde_Kolab_Storage_Folder_Types $types Handler of folder types.
     * @param Horde_Kolab_Storage_List_Query_List_Defaults $defaults Handler of defaults.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Folder_Types $types,
                                Horde_Kolab_Storage_List_Query_List_Defaults $defaults)
    {
        $this->_driver = $driver;
        $this->_folder_types = $types;
        $this->_defaults = $defaults;
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type handler as values.
     */
    private function listFolderTypeAnnotations()
    {
        $result = array();
        foreach ($this->_driver->listAnnotation(self::ANNOTATION_FOLDER_TYPE) as $folder => $annotation) {
            $result[$folder] = $this->_folder_types->create($annotation);
        }
        return $result;
    }

    /**
     * Returns the folder types as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type as values.
     */
    public function listTypes()
    {
        $result = array();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            $result[$folder] = $annotation->getType();
        }
        return $result;
    }

    /**
     * List all folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    public function listByType($type)
    {
        $result = array();
        foreach ($this->listTypes() as $folder => $folder_type) {
            if ($folder_type == $type) {
                $result[] = $folder;
            }
        }
        return $result;
    }

    /**
     * List basic folder data for the folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    public function dataByType($type)
    {
        $result = array();
        $namespace = $this->_driver->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $folder_type) {
            if ($folder_type->getType() == $type) {
                $data = new Horde_Kolab_Storage_Folder_Data(
                    $folder, $folder_type, $namespace
                );
                $result[$folder] = $data->toArray();
            }
        }
        return $result;
    }

    /**
     * List basic folder data for the specified folder.
     *
     * @param string $folder The folder path.
     *
     * @return array The folder data.
     */
    public function folderData($folder)
    {
        $list = $this->_driver->listFolders();
        if (!in_array($folder, $list)) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf('Folder %s does not exist!', $folder)
            );
        }
        $annotations = $this->listFolderTypeAnnotations();
        if (!isset($annotations[$folder])) {
            $type = $this->_folder_types->create('mail');
        } else {
            $type = $annotations[$folder];
        }
        $data = new Horde_Kolab_Storage_Folder_Data(
            $folder, $type, $this->_driver->getNamespace()
        );
        return $data->toArray();
    }

    /**
     * Get the folder owners.
     *
     * @return array The folder owners with the folder names as key and the
     *               owner as values.
     */
    public function listOwners()
    {
        $result = array();
        $namespace = $this->_driver->getNamespace();
        foreach ($this->_driver->listFolders() as $folder) {
            $result[$folder] = $namespace->getOwner($folder);
        }
        return $result;
    }

    /**
     * Set the specified folder as default for its current type.
     *
     * @param string $folder The folder name.
     */
    public function setDefault($folder)
    {
        $types = $this->listTypes();
        if (!isset($types[$folder])) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf(
                    "The folder %s has no Kolab type. It cannot be marked as 'default' folder!",
                    $folder
                )
            );
        }
        $previous = $this->getDefault($types[$folder]);
        if ($previous) {
            $this->_driver->setAnnotation($previous, self::ANNOTATION_FOLDER_TYPE, $types[$folder]);
        }
        $this->_driver->setAnnotation($folder, self::ANNOTATION_FOLDER_TYPE, $types[$folder] . '.default');
    }

    /**
     * Return the list of personal default folders.
     *
     * @return array An array that associates type (key) with the corresponding
     *               default folder name (value).
     */
    public function listPersonalDefaults()
    {
        return $this->_getPersonalDefaults();
    }

    /**
     * Return the list of default folders.
     *
     * @return array An array with owners as keys and another array as
     *               value. The second array associates type (key) with the
     *               corresponding default folder (value).
     */
    public function listDefaults()
    {
        return $this->_getDefaults();
    }

    /**
     * Get the default folder for a certain type.
     *
     * @param string $type The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    public function getDefault($type)
    {
        $defaults = $this->_getPersonalDefaults();
        if (!isset($defaults[$type])) {
            return false;
        } else {
            return $defaults[$type];
        }
    }

    /**
     * Get the default folder for a certain type from a different owner.
     *
     * @param string $owner The folder owner.
     * @param string $type  The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    public function getForeignDefault($owner, $type)
    {
        $defaults = $this->_getDefaults();
        if (!isset($defaults[$owner][$type])) {
            return false;
        } else {
            return $defaults[$owner][$type];
        }
    }

    /**
     * Return the list of personal defaults.
     */
    private function _getPersonalDefaults()
    {
        $this->_initDefaults();
        return $this->_defaults->getPersonalDefaults();
    }

    /**
     * Return the complete list of defaults.
     */
    private function _getDefaults()
    {
        $this->_initDefaults();
        return $this->_defaults->getDefaults();
    }

    /**
     * Initialize the list of defaults.
     */
    private function _initDefaults()
    {
        if (!$this->_defaults->isComplete()) {
            $namespace = $this->_driver->getNamespace();
            foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
                if ($annotation->isDefault()) {
                    $this->_defaults->rememberDefault(
                        $folder,
                        $annotation->getType(),
                        $namespace->getOwner($folder),
                        $namespace->matchNamespace($folder)->getType() == Horde_Kolab_Storage_Folder_Namespace::PERSONAL
                    );
                }
            }
            $this->_defaults->markComplete();
        }
    }

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    public function getStamp()
    {
        return pack('Nn', time(), mt_rand());
    }
}