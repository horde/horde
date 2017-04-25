<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */

/**
 * The base class that all compress drivers should extend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress
 */
class Horde_Compress_Base
{
    /**
     * Does this driver support compressing data?
     *
     * @var boolean
     */
    public $canCompress = false;

    /**
     * Does this driver support decompressing data?
     *
     * @var boolean
     */
    public $canDecompress = false;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    public function __construct($options = array())
    {
        if (!empty($options['logger'])) {
            $this->_logger = $options['logger'];
        } else {
            $this->_logger = new Horde_Support_Stub();
        }
    }

    /**
     * Compresses the data.
     *
     * @param mixed $data    The data to compress.
     * @param array $params  An array of arguments needed to compress the
     *                       data.
     *
     * @return mixed  The compressed data.
     * @throws Horde_Compress_Exception
     */
    public function compress($data, array $params = array())
    {
        return $data;
    }

    /**
     * Compresses a directory.
     *
     * @since Horde_Compress 2.2.0
     *
     * @param string $directory  The directory to recursively compress.
     * @param array $params      An array of arguments needed to compress the
     *                           data.
     *
     * @return mixed  The compressed data.
     * @throws Horde_Compress_Exception
     */
    public function compressDirectory($directory, array $params = array())
    {
        if (!$this->canCompress) {
            throw new Horde_Compress_Exception(
                Horde_Compress_Translation::t("Cannot compress data")
            );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            )
        );
        $regexp = '/^' . preg_quote($directory . '/', '/') . '/';
        $data = array();
        foreach ($iterator as $file) {
            $data[] = array(
                'name' => preg_replace($regexp, '', $file->getPathName()),
                'data' => $file->openFile()->fread($file->getSize()),
                'time' => $file->getMTime()
            );
        }

        return $this->compress($data, $params);
    }

    /**
     * Decompresses the data.
     *
     * @param mixed $data    The data to decompress.
     * @param array $params  An array of arguments needed to decompress the
     *                       data.
     *
     * @return mixed  The decompressed data.
     * @throws Horde_Compress_Exception
     */
    public function decompress($data, array $params = array())
    {
        return $data;
    }

}
