<?php
/**
 * Class for providing garbage collection for any VFS instance.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package VFS
 */
class VFS_GC
{
    /**
     * Garbage collect files in the VFS storage system.
     *
     * @param VFS $vfs       The VFS object to perform garbage collection on.
     * @param string $path   The VFS path to clean.
     * @param integer $secs  The minimum amount of time (in seconds) required
     *                       before a file is removed.
     */
    public function gc($vfs, $path, $secs = 345600)
    {
        /* A 1% chance we will run garbage collection during a call. */
        if (rand(0, 99) != 0) {
            return;
        }

        /* Use a backend-specific method if one exists. */
        if (is_callable(array($vfs, 'gc'))) {
            return $vfs->gc($path, $secs);
        }

        /* Make sure cleaning is done recursively. */
        try {
            $modtime = time() - $secs;
            foreach ($vfs->listFolder($path, null, true, false, true) as $val) {
                if ($val['date'] < $modtime) {
                    $vfs->deleteFile($path, $val['name']);
                }
            }
        } catch (VFS_Exception $e) {}
    }

}
