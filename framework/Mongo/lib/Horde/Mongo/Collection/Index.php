<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mongo
 */

/**
 * Implement index creation on a MongoDB collection.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mongo
 */
interface Horde_Mongo_Collection_Index
{
    /**
     * Are the indices up-to-date?
     *
     * @return boolean  True if up-to-date.
     */
    public function checkMongoIndices();

    /**
     * Ensures that collection indices are created.
     */
    public function createMongoIndices();

}
