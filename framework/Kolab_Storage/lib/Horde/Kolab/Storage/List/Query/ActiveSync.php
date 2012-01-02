<?php
/**
 * The interface of the query for active sync settings.
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
 * The interface of the query for active sync settings.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage_List_Query_ActiveSync
extends Horde_Kolab_Storage_List_Query
{
    /**
     * Returns the active sync settings.
     *
     * @param string $folder The folder name.
     *
     * @return array The folder active sync parameters.
     */
    public function getActiveSync($folder);

    /**
     * Set the active sync settings.
     *
     * @param string $folder The folder name.
     * @param array  $data   The active sync settings.
     *
     * @return string The encoded share parameters.
     */
    public function setActiveSync($folder, array $data);
}