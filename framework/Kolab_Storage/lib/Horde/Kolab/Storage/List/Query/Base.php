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
     * @param Horde_Kolab_Storage_List $list The queriable list.
     */
    public function __construct(Horde_Kolab_Storage_List $list)
    {
        $this->_list = $list;
    }

    /**
     * Inject the factory.
     *
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     *
     * @return NULL
     */
    public function setFactory(Horde_Kolab_Storage_Factory $factory)
    {
        $this->_factory = $factory;
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
        $list = $this->listFolderTypeAnnotations();
        foreach ($list as $folder => $annotation) {
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
        $list = $this->_list->listFolderTypes();
        foreach ($list as $folder => $annotation) {
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
     * Synchronize the query data with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
    }
}