<?php
/**
 * The basic list query.
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
 * The basic list query.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Query_Base
implements Horde_Kolab_Storage_List_Query
{
    /**
     * The queriable list.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

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
        $this->_list = $list;
        $this->_factory = $params['factory'];
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
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type handler as values.
     */
    public function listFolderTypeAnnotations()
    {
        $result = array();
        foreach ($this->_list->listFolderTypes() as $folder => $annotation) {
            $result[$folder] = $this->_factory->createFolderType($annotation);
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
        $namespace = $this->_list->getNamespace();
        foreach ($this->listTypes() as $folder => $folder_type) {
            if ($folder_type == $type) {
                $result[$folder] = array(
                    'owner' => $namespace->getOwner($folder),
                    'name' => $namespace->getTitle($folder),
                );
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
        $list = $this->_list->listFolders();
        if (!in_array($folder, $list)) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Folder %s does not exist!', $folder)
            );
        }
        $annotations = $this->listFolderTypeAnnotations();
        if (!isset($annotations[$folder])) {
            $type = $this->_factory->createFolderType('mail');
        } else {
            $type = $annotations[$folder];
        }
        $namespace = $this->_list->getNamespace();
        return array(
            'type' => $type->getType(),
            'default' => $type->isDefault(),
            'namespace' => $namespace->matchNamespace($folder)->getType(),
            'owner' => $namespace->getOwner($folder),
            'name' => $namespace->getTitle($folder),
            'subpath' => $namespace->getSubpath($folder),
        );
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
        $namespace = $this->_list->getNamespace();
        foreach ($this->_list->listFolders() as $folder) {
            $result[$folder] = $namespace->getOwner($folder);
        }
        return $result;
    }

    /**
     * Return the list of personal default folders.
     *
     * @return array An array that associates type (key) with the corresponding
     *               default folder name (value).
     */
    public function listPersonalDefaults()
    {
        $result = array();
        $namespace = $this->_list->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            if ($annotation->isDefault()
                && ($namespace->matchNamespace($folder)->getType()
                    == Horde_Kolab_Storage_Folder_Namespace::PERSONAL)) {
                $result[$annotation->getType()] = $folder;
            }
        }
        return $result;
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
        $result = array();
        $namespace = $this->_list->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            if ($annotation->isDefault()) {
                $result[$namespace->getOwner($folder)][$annotation->getType()] = $folder;
            }
        }
        return $result;
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
        $result = null;
        $namespace = $this->_list->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            if ($annotation->getType() == $type
                && $annotation->isDefault()
                && ($namespace->matchNamespace($folder)->getType()
                    == Horde_Kolab_Storage_Folder_Namespace::PERSONAL)) {
                if ($result === null) {
                    $result = $folder;
                } else {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf(
                            'Both folders %s and %s are marked as default folder of type %s!',
                            $result,
                            $folder,
                            $type
                        )
                    );
                }
            }
        }
        if ($result === null) {
            return false;
        } else {
            return $result;
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
        $result = null;
        $namespace = $this->_list->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            if ($annotation->getType() == $type
                && $annotation->isDefault()
                && ($namespace->getOwner($folder) == $owner)) {
                if ($result === null) {
                    $result = $folder;
                } else {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf(
                            'Both folders %s and %s are marked as default folder of type %s!',
                            $result,
                            $folder,
                            $type
                        )
                    );
                }
            }
        }
        if ($result === null) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * Synchronize the query data with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
    }
}