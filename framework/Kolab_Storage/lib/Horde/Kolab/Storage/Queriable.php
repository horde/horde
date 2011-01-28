<?php
/**
 * Marks a queriable class.
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
 * Marks a queriable class.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
interface Horde_Kolab_Storage_Queriable
{
    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_Query $query);

    /**
     * Return a registered query.
     *
     * @param string $name The query name.
     *
     * @return NULL
     */
    public function getQuery($name);
}

