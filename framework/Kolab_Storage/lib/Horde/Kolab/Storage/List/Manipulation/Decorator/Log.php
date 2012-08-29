<?php
/**
 * Logs Kolab folder list manipulations.
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
 * Logs Kolab folder list manipulations.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Manipulation_Decorator_Log
extends Horde_Kolab_Storage_List_Manipulation
{
    /**
     * Decorated manipulation handler.
     *
     * @var Horde_Kolab_Storage_List_Manipulation
     */
    private $_manipulation;

    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List_Manipulation $manipulation The list manipulator.
     * @param mixed $logger The log handler. This instance must provide the debug() method.
     */
    public function __construct(Horde_Kolab_Storage_List_Manipulation $manipulation, $logger)
    {
        $this->_manipulation = $manipulation;
        $this->_logger = $logger;
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
        $this->_logger->debug(sprintf('Creating folder %s.', $folder));
        $result = $this->_manipulation->createFolder($folder, $type);
        $this->_logger->debug(
            sprintf('Successfully created folder %s [type: %s].', $folder, $type)
        );
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
        $this->_logger->debug(sprintf('Deleting folder %s.', $folder));
        $result = $this->_manipulation->deleteFolder($folder);
        $this->_logger->debug(
            sprintf('Successfully deleted folder %s.', $folder)
        );
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
        $this->_logger->debug(sprintf('Renaming folder %s.', $old));
        $result = $this->_manipulation->renameFolder($old, $new);
        $this->_logger->debug(
            sprintf('Successfully renamed folder %s to %s.', $old, $new)
        );
    }

    /**
     * Register a new manipulation listener.
     *
     * @param Horde_Kolab_Storage_List_Manipulation_Listener $listener The new listener.
     */
    public function registerListener(Horde_Kolab_Storage_List_Manipulation_Listener $listener)
    {
        $this->_manipulation->registerListener($listener);
    }
}