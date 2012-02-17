<?php
/**
 * The Horde_Compress_Gzip class allows gzip files to be read.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Gzip extends Horde_Compress_Base
{
    /**
     */
    public $canDecompress = true;

    /**
     * Gzip file flags.
     *
     * @var array
     */
    protected $_flags = array(
        'FTEXT'     =>  0x01,
        'FHCRC'     =>  0x02,
        'FEXTRA'    =>  0x04,
        'FNAME'     =>  0x08,
        'FCOMMENT'  =>  0x10
    );

    /**
     * @return string  The uncompressed data.
     */
    public function decompress($data, array $params = array())
    {
        /* If gzip is not compiled into PHP, return now. */
        if (!Horde_Util::extensionExists('zlib')) {
            throw new Horde_Compress_Exception(Horde_Compress_Translation::t("This server can't uncompress gzip files."));
        }

        /* Gzipped File - decompress it first. */
        $position = 0;
        $info = @unpack('CCM/CFLG/VTime/CXFL/COS', substr($data, $position + 2));
        if (!$info) {
            throw new Horde_Compress_Exception(Horde_Compress_Translation::t("Unable to decompress data."));
        }
        $position += 10;

        if ($info['FLG'] & $this->_flags['FEXTRA']) {
            $XLEN = unpack('vLength', substr($data, $position + 0, 2));
            $XLEN = $XLEN['Length'];
            $position += $XLEN + 2;
        }

        if ($info['FLG'] & $this->_flags['FNAME']) {
            $filenamePos = strpos($data, "\x0", $position);
            $filename = substr($data, $position, $filenamePos - $position);
            $position = $filenamePos + 1;
        }

        if ($info['FLG'] & $this->_flags['FCOMMENT']) {
            $commentPos = strpos($data, "\x0", $position);
            $comment = substr($data, $position, $commentPos - $position);
            $position = $commentPos + 1;
        }

        if ($info['FLG'] & $this->_flags['FHCRC']) {
            $hcrc = unpack('vCRC', substr($data, $position + 0, 2));
            $hcrc = $hcrc['CRC'];
            $position += 2;
        }

        $result = @gzinflate(substr($data, $position, strlen($data) - $position));
        if (empty($result)) {
            throw new Horde_Compress_Exception(Horde_Compress_Translation::t("Unable to decompress data."));
        }

        return $result;
    }

}
