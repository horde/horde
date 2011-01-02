<?php
/**
 * The log decorator for folder lists from Kolab storage.
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
 * The log decorator for folder lists from Kolab storage.
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
class Horde_Kolab_Storage_List_Decorator_Log
implements Horde_Kolab_Storage_List
{
    /**
     * Decorated list handler.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The original list handler.
     * @param mixed                    $logger The log handler. This instance
     *                                         must provide the info() method.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        $logger
    ) {
        $this->_list = $list;
        $this->_logger = $logger;
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $this->_logger->info('Listing folders.');
        $result = $this->_list->listFolders();
        $this->_logger->info(
            sprintf('List contained %s folders.', count($result))
        );
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
        $this->_logger->info('Listing folder types.');
        $result = $this->_list->listTypes();
        $this->_logger->info(
            sprintf('List contained %s folders and types.', count($result))
        );
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
        $this->_logger->info('Listing folder type annotations.');
        $result = $this->_list->listFolderTypeAnnotations();
        $this->_logger->info(
            sprintf('List contained %s folders and annotations.', count($result))
        );
        return $result;
    }

    /**
     * Return the specified query type.
     *
     * @param string $name The query name.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    public function getQuery($name)
    {
        return $this->_list->getQueryWithParent($name, $this);
    }

}