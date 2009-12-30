<?php
/**
 * @category Horde
 * @package  Horde_Vfs
 */

/**
 * A file-backed Vfs adapter that uses a database for the filesystem tree and
 * metadata, and stores file contents in chunks in a separate blob table. Large
 * files can be split between many chunks; small files just take one.
 *
 * @category Horde
 * @package  Horde_Vfs
 */
class Horde_Vfs_Adapter_Db_Chunked extends Horde_Vfs_Adapter_Db_Base
{
}
