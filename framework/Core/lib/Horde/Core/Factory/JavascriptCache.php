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
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */

/**
 * A Horde_Injector based factory for creating the Javascript caching object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_Factory_JavascriptCache extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $driver = empty($conf['cachejs'])
            ? 'none'
            : Horde_String::lower($conf['cachejsparams']['driver']);

        switch ($driver) {
        case 'filesystem':
            $driver = 'Horde_Script_Cache_File';
            $params = $conf['cachejsparams'];
            break;

        case 'horde_cache':
            $driver = 'Horde_Script_Cache_HordeCache';
            $params = $conf['cachejsparams'];
            break;

        case 'none':
        default:
            $driver = 'Horde_Script_Cache_Null';
            $params = array();
            break;
        }

        return new $driver($params);
    }

}
