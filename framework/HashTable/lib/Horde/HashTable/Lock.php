<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */

/**
 * Extension to base HashTable class by adding lock methods to prevent access
 * from other PHP processes.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */
interface Horde_HashTable_Lock
{
    /**
     * Obtain lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function lock($key);

    /**
     * Release lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function unlock($key);

}
