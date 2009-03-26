<?php
/**
 * @category Horde
 * @package  Horde_Vfs
 */

/**
 * A file-backed Vfs adapter that uses a database for the filesystem tree and
 * metadata, but stores content using the operating system's filesystem
 * (probably local, but could be NFS or another distributed fs).
 *
 * @category Horde
 * @package  Horde_Vfs
 */
class Horde_Vfs_Adapter_Db_File extends Horde_Vfs_Adapter_Db_Base
{
}
