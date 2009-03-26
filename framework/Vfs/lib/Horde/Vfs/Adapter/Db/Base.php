<?php
/**
 * @category Horde
 * @package  Horde_Vfs
 */

/**
 * This Db base class manages the horde_vfs_metadata table for the various Db
 * backends (Chunked, File, S3). The metadata table holds the filesystem
 * representation in the database, but actual file contents are not stored
 * thre. A subclass is needed to provide actual file storage.
 *
 * @category Horde
 * @package  Horde_Vfs
 */
abstract class Horde_Vfs_Adapter_Db_Base
{
}
