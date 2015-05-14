<?php
/**
 * Represents a query.
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
 * Represents a query.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
interface Horde_Kolab_Storage_Query
{
    /**
     * Synchronize the query data with the information from the backend.
     *
     * @param array $params Additional parameters may contain:
     *   - current_sync: (integer) Timestamp of the current sync.
     *   - last_sync:    (integer) Timestamp containing the time of last sync.
     *   - changes:      (array)   An array of arrays keyed by backend id
     *                             containing information about each change.
     */
    public function synchronize($params = array());
}

