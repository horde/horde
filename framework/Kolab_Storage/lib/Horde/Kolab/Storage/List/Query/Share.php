<?php
/**
 * The interface of the share information query.
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
 * The interface of the share information query.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_List_Query_Share
{
    /**
     * Returns the share description.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share description.
     */
    abstract public function getDescription($folder);

    /**
     * Returns the share parameters.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share parameters.
     */
    abstract public function getParameters($folder);

    /**
     * Returns the share description.
     *
     * @param string $folder      The folder name.
     * @param string $description The share description.
     *
     * @return string The folder/share description.
     */
    abstract public function setDescription($folder, $description);

    /**
     * Returns the share parameters.
     *
     * @param string $folder     The folder name.
     * @param array  $parameters The share parameters.
     *
     * @return string The folder/share parameters.
     */
    abstract public function setParameters($folder, array $parameters);
}