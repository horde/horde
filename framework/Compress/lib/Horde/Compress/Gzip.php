<?php
/**
 * The Horde_Compress_Gzip class allows gzip files to be read.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Compress
 */
class Horde_Compress_Gzip extends Horde_Compress
{
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
     * Decompress a gzip file and get information from it.
     *
     * @param string $data   The tar file data.
     * @param array $params  The parameter array (Unused).
     *
     * @return string  The uncompressed data.
     * @throws Horde_Exception
     */
    public function decompress($data, $params = array())
    {
        /* If gzip is not compiled into PHP, return now. */
        if (!Horde_Util::extensionExists('zlib')) {
            throw new Horde_Exception(Horde_Compress_Translation::t("This server can't uncompress gzip files."));
        }

        /* Gzipped File - decompress it first. */
        $position = 0;
        $info = @unpack('CCM/CFLG/VTime/CXFL/COS', substr($data, $position + 2));
        if (!$info) {
            throw new Horde_Exception(Horde_Compress_Translation::t("Unable to decompress data."));
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
            throw new Horde_Exception(Horde_Compress_Translation::t("Unable to decompress data."));
        }

        return $result;
    }

}
