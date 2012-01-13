<?php
/**
 * Defines a parser interface for converters that turn Kolab data objects into
 * arrays.
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
 * Defines a parser interface for converters that turn Kolab data objects into
 * arrays.
er.
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
interface Horde_Kolab_Storage_Data_Parser
{
    /**
     * Fetches the objects for the specified backend IDs.
     *
     * @param string $folder  The folder to access.
     * @param array  $obids   The object backend IDs to fetch.
     * @param array  $options Additional options for fetching.
     *
     * @return array The parsed objects.
     */
    public function fetch($folder, $obids, $options = array());

    /**
     * Return the format handler.
     *
     * @return Horde_Kolab_Storage_Data_Format The data object <-> format
     *                                         bridge.
     */
    public function getFormat();
}
