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
 * @package   Core
 */

/**
 * Null backend for the javascript caching library (directly outputs original
 * scripts).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Script_Cache_Null extends Horde_Script_Cache
{
    /**
     */
    protected function _process($scripts, $full = false)
    {
        $out = array();

        foreach ($scripts as $val) {
            $out[] = strval($full ? $val->url_full : $val->url);
        }

        return $out;
    }

}
