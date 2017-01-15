<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=History
 * @package   History
 */

/**
 * A null implementation of the history storage backend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=History
 * @package   History
 */
class Horde_History_Null extends Horde_History
{
    /**
     */
    public function setCache(Horde_Cache $cache)
    {
        /* Override parent and don't set the cache object. */
    }

    /**
     */
    protected function _log(
        Horde_History_Log $history, array $attributes, $replaceAction = false
    )
    {
    }

    /**
     */
    public function _getHistory($guid)
    {
        return new Horde_History_Log($guid, array());
    }

    /**
     */
    public function _getByTimestamp(
        $cmp, $ts, array $filters = array(), $parent = null
    )
    {
        return array();
    }

    /**
     */
    public function removeByNames(array $names)
    {
    }

}
