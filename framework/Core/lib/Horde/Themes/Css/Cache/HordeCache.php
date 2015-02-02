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
 * Horde_Cache backend for the CSS caching library.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Themes_Css_Cache_HordeCache extends Horde_Themes_Css_Cache
{
    /**
     */
    public function process($css, $cacheid)
    {
        global $injector;

        if (!empty($this->_params['filemtime'])) {
            foreach ($css as &$val) {
                $val['mtime'] = @filemtime($val['fs']);
            }
        }

        $cache = $injector->getInstance('Horde_Cache');
        $sig = hash(
            /* Use 64-bit FNV algo (instead of 32-bit) since this is a
             * publicly accessible key and we want to guarantee filename
             * is unique. */
            (version_compare(PHP_VERSION, '5.4', '>=')) ? 'fnv164' : 'sha1',
            json_encode($css) . $cacheid
        );

        // Do lifetime checking here, not on cache display page.
        if (!$cache->exists($sig, empty($this->_params['lifetime']) ? 0 : $this->_params['lifetime'])) {
            $compress = new Horde_Themes_Css_Compress();
            $cache->set($sig, $compress->compress($css));
        }

        return array(
            Horde::getCacheUrl('css', array('cid' => $sig))
        );
    }

}
