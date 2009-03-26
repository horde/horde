<?php
/**
 * @category Horde
 * @package  Horde_Vfs
 */

/**
 * @category Horde
 * @package  Horde_Vfs
 */
class Horde_Vfs_File
{
    /**
     * Return a URL that a file can be accessed from. For example, an S3 URL, or
     * a local file with vfs-direct and filesystem backends, or a passthrough
     * script using the DownloadController.
     *
     * @return string
     */
    public function getUri()
    {
    }

    /**
     * Return a UUID for this file.
     *
     * @TODO useful? Could be used for indexing, for private URLs, for primary
     * keys in Horde_Content, ...?
     */
    public function getUuid()
    {
    }

}
