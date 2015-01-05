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
 * Filesystem backend for the CSS caching library.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Themes_Css_Cache_File extends Horde_Themes_Css_Cache
{
    /**
     */
    public function process($css, $cacheid)
    {
        global $registry;

        if (!empty($this->_params['filemtime'])) {
            foreach ($css as &$val) {
                $val['mtime'] = @filemtime($val['fs']);
            }
        }

        $sig = hash(
            /* Use 64-bit FNV algo (instead of 32-bit) since this is a
             * publicly accessible key and we want to guarantee filename
             * is unique. */
            (PHP_MINOR_VERSION >= 4) ? 'fnv164' : 'sha1',
            json_encode($css) . $cacheid
        );
        $filename = $sig . '.css';
        $js_fs = $registry->get('staticfs', 'horde');
        $path = $js_fs . '/' . $filename;

        if (!file_exists($path)) {
            $compress = new Horde_Themes_Css_Compress();
            $temp = Horde_Util::getTempFile('staticcss', true, $js_fs);
            if (!file_put_contents($temp, $compress->compress($css), LOCK_EX) ||
                !chmod($temp, 0777 & ~umask()) ||
                !rename($temp, $path)) {
                Horde::log('Could not write cached CSS file to disk.', 'EMERG');
                return array();
            }
        }

        return array(
             Horde::url($registry->get('staticuri', 'horde') . '/' . $filename, true, array('append_session' => -1))
         );
    }

    /**
     */
    public function gc()
    {
        global $registry;

        if (empty($this->_params['lifetime'])) {
            return;
        }

        /* Keep a file in the static directory that prevents us from doing
         * garbage collection more than once a day. */
        $curr_time = time();
        $static_dir = $registry->get('fileroot', 'horde') . '/static';
        $static_stat = $static_dir . '/gc_cachecss';

        $next_run = !is_readable($static_stat) ?: @file_get_contents($static_stat);

        if (!$next_run || ($curr_time > $next_run)) {
            file_put_contents($static_stat, $curr_time + 86400);
        }

        if (!$next_run || ($curr_time < $next_run)) {
            return;
        }

        $curr_time -= $this->_params['lifetime'];
        $removed = 0;

        foreach (glob($static_dir . '/*.css') as $file) {
            if ($curr_time > filemtime($file)) {
                @unlink($file);
                ++$removed;
            }
        }

        Horde::log(
            sprintf('Cleaned out static CSS files (removed %d file(s)).', $removed),
            'DEBUG'
        );
    }

}
