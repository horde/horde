<?php
/**
 * Class for providing garbage collection for any VFS instance.
 *
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vfs
 */
class Horde_Vfs_Gc
{
    /**
     * Garbage collect files in the VFS storage system.
     *
     * @param VFS $vfs       The VFS object to perform garbage collection on.
     * @param string $path   The VFS path to clean.
     * @param integer $secs  The minimum amount of time (in seconds) required
     *                       before a file is removed.
     */
    public static function gc($vfs, $path, $secs = 345600)
    {
        /* A 0.1% chance we will run garbage collection during a call. */
        if (substr(time(), -3) !== '000') {
            return;
        }

        /* Use a backend-specific method if one exists. */
        if (is_callable(array($vfs, 'gc'))) {
            $vfs->gc($path, $secs);
        }

        /* Make sure cleaning is done recursively. */
        try {
            $modtime = time() - $secs;
            foreach ($vfs->listFolder($path, null, true, false, true) as $val) {
                if ($val['date'] < $modtime) {
                    $vfs->deleteFile($path, $val['name']);
                }
            }
        } catch (Horde_Vfs_Exception $e) {
        }
    }
}
