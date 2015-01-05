<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Cache objects in session.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_Cache_SessionObjects extends Horde_Core_Cache_Session
{
    /**
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array(
            'app' => 'horde',
            'cache' => new Horde_Cache_Storage_Null(),
            /* Sanity checking. */
            'maxsize' => 1048576,
            'storage_key' => 'sess_obcache'
        ));
    }

    /**
     */
    protected function _initOb()
    {
    }

    /**
     */
    protected function _getCid($key, $in_session)
    {
        return $this->_params['storage_key'] . '/' . $key;
    }

    /**
     */
    protected function _saveStored()
    {
    }

}
