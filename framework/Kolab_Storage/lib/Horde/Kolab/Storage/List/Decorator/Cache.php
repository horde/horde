<?php
/**
 * The cache decorator for folder lists from Kolab storage.
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
 * The cache decorator for folder lists from Kolab storage.
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
class Horde_Kolab_Storage_List_Decorator_Cache
implements Horde_Kolab_Storage_List
{
    /**
     * Decorated list handler.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List  $list  The original list handler.
     * @param Horde_Kolab_Storage_Cache $cache The cache storing data for this
     *                                         decorator.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        Horde_Kolab_Storage_Cache $cache
    ) {
        $this->_list = $list;
        $this->_cache = $cache;
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $result = $this->_list->listFolders();
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
        $result = $this->_list->listTypes();
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
        $result = $this->_list->listFolderTypeAnnotations();
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