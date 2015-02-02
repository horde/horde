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
 * Horde_Cache backend for the javascript caching library.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Script_Cache_HordeCache extends Horde_Script_Cache
{
    /**
     * Cached javascript minification object.
     *
     * @var Horde_Script_Compress
     */
    protected $_jsmin;

    /**
     */
    protected function _process($scripts)
    {
        global $injector;

        if (empty($scripts)) {
            return array();
        }

        $tmp = array();
        foreach ($scripts as $val) {
            $tmp[] = $val->modified;
        }
        $mtime = max($tmp);

        $hashes = array_keys($scripts);
        sort($hashes);

        $sig = hash(
            (version_compare(PHP_VERSION, '5.4', '>=')) ? 'fnv164' : 'sha1',
            json_encode($hashes) . $mtime
        );

        $cache = $injector->getInstance('Horde_Cache');
        $cache_lifetime = empty($this->_params['lifetime'])
            ? 0
            : $this->_params['lifetime'];

        // Do lifetime checking here, not on cache display page.
        $js_url = Horde::getCacheUrl('js', array('cid' => $sig));

        $out = array($js_url);

        if ($cache->exists($sig, $cache_lifetime)) {
            return $out;
        }

        /* Check for existing process creating compressed file. Maximum 15
         * seconds wait time. */
        for ($i = 0; $i < 15; ++$i) {
            if ($cache->exists($sig . '.lock')) {
                sleep(1);
            } elseif ($i) {
                return $out;
            } else {
                $cache->set($sig . '.lock', 1);
                break;
            }
        }

        if (!isset($this->_compress)) {
            $this->_compress = new Horde_Script_Compress(
                $this->_params['compress'],
                $this->_params
            );
        }

        $sourcemap_url = Horde::getCacheUrl('js', array('cid' => $sig . '.map'));
        $jsmin = $this->_compress->getMinifier($scripts, $sourcemap_url);

        $cache->set($sig, $jsmin->minify());
        if ($this->_compress->sourcemap_support) {
            $cache->set($sig . '.map', $jsmin->sourcemap());
        }
        $cache->expire($sig . '.lock');

        return $out;
    }

}
