<?php
/**
 * The cached list query.
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
 * The cached list query.
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
class Horde_Kolab_Storage_List_Query_Decorator_Cache
implements Horde_Kolab_Storage_List_Query
{
    /**
     * The queriable list.
     *
     * @var Horde_Kolab_Storage_Queriable
     */
    private $_queriable;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Queriable $queriable The queriable list.
     */
    public function __construct(Horde_Kolab_Storage_Queriable $queriable)
    {
        $this->_queriable = $queriable;
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
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type handler as values.
     */
    public function listFolderTypeAnnotations()
    {
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