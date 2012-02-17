<?php
/**
 * The basic handler for accessing folder lists from Kolab storage.
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
 * The basic handler for accessing folder lists from Kolab storage.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Base
implements Horde_Kolab_Storage_List, Horde_Kolab_Storage_List_Query
{
    /** The folder type annotation */
    const ANNOTATION_FOLDER_TYPE = '/shared/vendor/kolab/folder-type';

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * The list of registered queries.
     *
     * @var array
     */
    private $_queries = array();

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver      $driver  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory     $factory The factory.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Factory $factory)
    {
        $this->_driver  = $driver;
        $this->_factory = $factory;
    }

    /**
     * Return the list driver.
     *
     * @return Horde_Kolab_Storage_Driver The driver.
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getId()
    {
        return $this->_driver->getId();
    }

    /**
     * Return the connection parameters.
     *
     * @return array The connection parameters.
     */
    public function getIdParameters()
    {
        return $this->_driver->getParameters();
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
        $this->_driver->create($folder);
        if ($type) {
            $this->_driver->setAnnotation(
                $folder, self::ANNOTATION_FOLDER_TYPE, $type
            );
        }
        foreach ($this->_queries as $name => $query) {
            $query->createFolder($folder, $type);
        }
    }

    /**
     * Delete a folder.
     *
     * WARNING: Do not use this call in case there is still data present in the
     * folder. You are required to empty any data set *before* removing the
     * folder. Otherwise there is no guarantee you can adhere to that Kolab
     * specification that might require the triggering of remote systems to
     * inform them about the removal of the folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
        $this->_driver->delete($folder);
        foreach ($this->_queries as $name => $query) {
            $query->deleteFolder($folder);
        }
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
        $this->_driver->rename($old, $new);
        foreach ($this->_queries as $name => $query) {
            $query->renameFolder($old, $new);
        }
    }

    /**
     * Returns a representation for the requested folder.
     *
     * @param string $folder The path of the folder to return.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function getFolder($folder)
    {
        return $this->_factory->createFolder(
            $this,
            $folder
        );
    }

    /**
     * Mark the specified folder as the default folder of this type.
     *
     * @param string $folder The path of the folder to mark as default.
     *
     * @return NULL
     */
    public function setDefault($folder)
    {
        /* $result = array(); */
        /* $namespace = $this->getNamespace(); */
        /* $list = $this->listFolderTypes(); */
        /* foreach ($list as $name => $annotation) { */
        /*     $result[$name] = $this->_factory->createFolderType($annotation); */
        /* } */
        /* if (!isset($result[$folder])) { */
        /*     $type = 'mail'; */
        /* } else { */
        /*     $type = $result[$folder]->getType(); */
        /* } */
        /* foreach ($result as $name => $annotation) { */
        /*     if ($annotation->getType() == $type */
        /*         && $annotation->isDefault() */
        /*         && ($namespace->matchNamespace($name)->getType() */
        /*             == Horde_Kolab_Storage_Folder_Namespace::PERSONAL)) { */
        /*         $this->_driver->setAnnotation( */
        /*             $name, */
        /*             self::ANNOTATION_FOLDER_TYPE, */
        /*             $type */
        /*         ); */
        /*     } */
        /* } */
        /* $this->_driver->setAnnotation( */
        /*     $folder, */
        /*     self::ANNOTATION_FOLDER_TYPE, */
        /*     $type . '.default' */
        /* ); */
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        return $this->_driver->listFolders();
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function listFolderTypes()
    {
        return $this->_driver->listAnnotation(
            self::ANNOTATION_FOLDER_TYPE
        );
    }

    /**
     * Returns the namespace for the list.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function getNamespace()
    {
        return $this->_driver->getNamespace();
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

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        foreach ($this->_queries as $name => $query) {
            $query->synchronize($params);
        }
    }

    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_Query $query)
    {
        if (!$query instanceOf Horde_Kolab_Storage_List_Query) {
            throw new Horde_Kolab_Storage_Exception(
                'The provided query is no list query.'
            );
        }
        $this->_queries[$name] = $query;
    }

    /**
     * Return a registered query.
     *
     * @param string $name The query name.
     *
     * @return Horde_Kolab_Storage_Query The requested query.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query does
     *                                       not exist.
     */
    public function getQuery($name = null)
    {
        if ($name === null) {
            $name = self::QUERY_BASE;
        }
        if (isset($this->_queries[$name])) {
            return $this->_queries[$name];
        } else {
            throw new Horde_Kolab_Storage_Exception('No such query!');
        }
    }
}