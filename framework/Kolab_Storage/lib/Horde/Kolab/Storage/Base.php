<?php
/**
 * The basic handler for accessing data from Kolab storage.
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
 * The basic handler for accessing data from Kolab storage.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Base
implements Horde_Kolab_Storage
{
    /**
     * The master Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_master;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver  $master  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory $factory The factory.
.
     */
    public function __construct(
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_Factory $factory
    ) {
        $this->_master  = $master;
        $this->_factory = $factory;
    }

    /**
     * Get the folder list object.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->_master,
            $this->_factory
        );
        $list->registerQuery(
            'Base', $this->_factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_Base', $list
            )
        );
        return $list;
    }

    /**
     * Get a Folder object.
     *
     * @param string $folder The folder name.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function getFolder($folder)
    {
        return new Horde_Kolab_Storage_Folder_Base(
            $this, $this->_master, $folder
        );
    }

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param string $folder The name of the folder.
     * @param string $type   The type of data we want to
     *                       access in the folder.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData($folder, $type)
    {
    }

}