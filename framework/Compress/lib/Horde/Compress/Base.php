<?php
/**
 * The base class that all compress drivers should extend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
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
     * Compress the data.
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
     * Decompress the data.
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
