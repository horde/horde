<?php
/**
 * The Horde_Compress_zip class allows ZIP files to be created and read.
 *
 * The ZIP compression code is partially based on code from:
 *   Eric Mueller <eric@themepark.com>
 *   http://www.zend.com/codex.php?id=535&single=1
 *
 *   Deins125 <webmaster@atlant.ru>
 *   http://www.zend.com/codex.php?id=470&single=1
 *
 * The ZIP compression date code is partially based on code from
 *   Peter Listiak <mlady@users.sourceforge.net>
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Compress
 */
class Horde_Compress_Zip extends Horde_Compress
{
    /* Constants used with decompress(). */
    const ZIP_LIST = 1;
    const ZIP_DATA = 2;

    /* Beginning of central directory record. */
    const CTRL_DIR_HEADER = "\x50\x4b\x01\x02";

    /* End of central directory record. */
    const CTRL_DIR_END = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /* Beginning of file contents. */
    const FILE_HEADER = "\x50\x4b\x03\x04";

    /**
     * ZIP compression methods.
     *
     * @var array
     */
    protected $_methods = array(
        0x0 => 'None',
        0x1 => 'Shrunk',
        0x2 => 'Super Fast',
        0x3 => 'Fast',
        0x4 => 'Normal',
        0x5 => 'Maximum',
        0x6 => 'Imploded',
        0x8 => 'Deflated'
    );

    /**
     * Temporary data for compressing files.
     *
     * @var array
     */
    protected $_ctrldir;

    /**
     * Temporary contents for compressing files.
     *
     * @var resource
     */
    protected $_tmp;

    /**
     * Create a ZIP compressed file from an array of file data.
     *
     * @param array $data    The data to compress.
     * <pre>
     * Requires an array of arrays - each subarray should contain the
     * following fields:
     * 'data' - (string) The data to compress.
     * 'name' - (string) The pathname to the file.
     * 'time' - (integer) [optional] The timestamp to use for the file.
     * </pre>
     * @param array $params  The parameter array.
     * <pre>
     * 'stream' - (boolean) If set, return a stream instead of a string.
     *            DEFAULT: Return string
     * </pre>
     *
     * @return mixed  The ZIP file as either a string or a stream resource.
     * @throws Horde_Exception
     */
    public function compress($data, $params = array())
    {
        if (!Horde_Util::extensionExists('zlib')) {
            throw new Horde_Exception(Horde_Compress_Translation::t("This server can't compress zip files."));
        }

        $this->_ctrldir = array();
        $this->_tmp = fopen('php://temp', 'r+');

        reset($data);
        while (list(, $val) = each($data)) {
            $this->_addToZipFile($val);
        }

        /* Creates the ZIP file.
         * Official ZIP file format: http://www.pkware.com/appnote.txt */
        $dir = implode('', $this->_ctrldir);

        fseek($this->_tmp, 0, SEEK_END);
        $offset = ftell($this->_tmp);

        fwrite($this->_tmp,
            $dir . self::CTRL_DIR_END .
            /* Total # of entries "on this disk". */
            pack('v', count($this->_ctrldir)) .
            /* Total # of entries overall. */
            pack('v', count($this->_ctrldir)) .
            /* Size of central directory. */
            pack('V', strlen($dir)) .
            /* Offset to start of central dir. */
            pack('V', $offset) .
            /* ZIP file comment length. */
            "\x00\x00"
        );

        rewind($this->_tmp);

        if (empty($params['stream'])) {
            $out = stream_get_contents($this->_tmp);
            fclose($this->_tmp);
        } else {
            $out = $this->_tmp;
        }

        return $out;
    }

    /**
     * Decompress a ZIP file and get information from it.
     *
     * @param string $data   The zipfile data.
     * @param array $params  The parameter array.
     * <pre>
     * The following parameters are REQUIRED:
     * 'action' - (integer) The action to take on the data.  Either
     *                      self::ZIP_LIST or self::ZIP_DATA.
     *
     * The following parameters are REQUIRED for self::ZIP_DATA also:
     * 'info' - (array) The zipfile list.
     * 'key' - (integer) The position of the file in the archive list.
     * </pre>
     *
     * @return mixed  The requested data.
     * @throws Horde_Exception
     */
    public function decompress($data, $params)
    {
        if (isset($params['action'])) {
            switch ($params['action']) {
            case self::ZIP_LIST:
                return $this->_getZipInfo($data);

            case self::ZIP_DATA:
                return $this->_getZipData($data, $params['info'], $params['key']);
            }
        }
    }

    /**
     * Get the list of files/data from the zip archive.
     *
     * @param string $data  The zipfile data.
     *
     * @return array  KEY: Position in zipfile
     *                VALUES:
     * <pre>
     * 'attr'    --  File attributes
     * 'crc'     --  CRC checksum
     * 'csize'   --  Compressed file size
     * 'date'    --  File modification time
     * 'name'    --  Filename
     * 'method'  --  Compression method
     * 'size'    --  Original file size
     * 'type'    --  File type
     * </pre>
     *
     * @throws Horde_Exception
     */
    protected function _getZipInfo($data)
    {
        $entries = array();

        /* Get details from Central directory structure. */
        $fhStart = strpos($data, self::CTRL_DIR_HEADER);

        do {
            if (strlen($data) < $fhStart + 31) {
                throw new Horde_Exception(Horde_Compress_Translation::t("Invalid ZIP data"));
            }
            $info = unpack('vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength', substr($data, $fhStart + 10, 20));
            $name = substr($data, $fhStart + 46, $info['Length']);

            $entries[$name] = array(
                'attr' => null,
                'crc' => sprintf("%08s", dechex($info['CRC32'])),
                'csize' => $info['Compressed'],
                'date' => null,
                '_dataStart' => null,
                'name' => $name,
                'method' => $this->_methods[$info['Method']],
                '_method' => $info['Method'],
                'size' => $info['Uncompressed'],
                'type' => null
            );

            $entries[$name]['date'] =
                mktime((($info['Time'] >> 11) & 0x1f),
                       (($info['Time'] >> 5) & 0x3f),
                       (($info['Time'] << 1) & 0x3e),
                       (($info['Time'] >> 21) & 0x07),
                       (($info['Time'] >> 16) & 0x1f),
                       ((($info['Time'] >> 25) & 0x7f) + 1980));

            if (strlen($data) < $fhStart + 43) {
                throw new Horde_Exception(Horde_Compress_Translation::t("Invalid ZIP data"));
            }
            $info = unpack('vInternal/VExternal', substr($data, $fhStart + 36, 6));

            $entries[$name]['type'] = ($info['Internal'] & 0x01) ? 'text' : 'binary';
            $entries[$name]['attr'] =
                (($info['External'] & 0x10) ? 'D' : '-') .
                (($info['External'] & 0x20) ? 'A' : '-') .
                (($info['External'] & 0x03) ? 'S' : '-') .
                (($info['External'] & 0x02) ? 'H' : '-') .
                (($info['External'] & 0x01) ? 'R' : '-');
        } while (($fhStart = strpos($data, self::CTRL_DIR_HEADER, $fhStart + 46)) !== false);

        /* Get details from local file header. */
        $fhStart = strpos($data, self::FILE_HEADER);

        $data_len = strlen($data);

        do {
            if ($data_len < $fhStart + 34) {
                throw new Horde_Exception(Horde_Compress_Translation::t("Invalid ZIP data"));
            }
            $info = unpack('vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength/vExtraLength', substr($data, $fhStart + 8, 25));
            $name = substr($data, $fhStart + 30, $info['Length']);
            $entries[$name]['_dataStart'] = $fhStart + 30 + $info['Length'] + $info['ExtraLength'];
        } while ($data_len > $fhStart + 30 + $info['Length'] &&
                 ($fhStart = strpos($data, self::FILE_HEADER, $fhStart + 30 + $info['Length'])) !== false);

        return array_values($entries);
    }

    /**
     * Returns the data for a specific archived file.
     *
     * @param string $data  The zip archive contents.
     * @param array $info   The information array from _getZipInfo().
     * @param integer $key  The position of the file in the archive.
     *
     * @return string  The file data.
     */
    protected function _getZipData($data, $info, $key)
    {
        if (($info[$key]['_method'] == 0x8) &&
            Horde_Util::extensionExists('zlib')) {
            /* If the file has been deflated, and zlib is installed,
               then inflate the data again. */
            return @gzinflate(substr($data, $info[$key]['_dataStart'], $info[$key]['csize']));
        } elseif ($info[$key]['_method'] == 0x0) {
            /* Files that aren't compressed. */
            return substr($data, $info[$key]['_dataStart'], $info[$key]['csize']);
        }

        return '';
    }

    /**
     * Checks to see if the data is a valid ZIP file.
     *
     * @param string $data  The ZIP file data.
     *
     * @return boolean  True if valid, false if invalid.
     */
    public function checkZipData($data)
    {
        return (strpos($data, self::FILE_HEADER) !== false);
    }

    /**
     * Converts a UNIX timestamp to a 4-byte DOS date and time format
     * (date in high 2-bytes, time in low 2-bytes allowing magnitude
     * comparison).
     *
     * @param integer $unixtime  The current UNIX timestamp.
     *
     * @return integer  The current date in a 4-byte DOS format.
     */
    protected function _unix2DOSTime($unixtime = null)
    {
        $timearray = (is_null($unixtime)) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) |
                ($timearray['mon'] << 21) |
                ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) |
                ($timearray['minutes'] << 5) |
                ($timearray['seconds'] >> 1);
    }

    /**
     * Adds a "file" to the ZIP archive.
     *
     * @param array $file  See self::createZipFile().
     */
    protected function _addToZipFile($file)
    {
        if (is_resource($file['data'])) {
            rewind($file['data']);
            $data = stream_get_contents($file['data']);
        } else {
            $data = $file['data'];
        }

        $name = str_replace('\\', '/', $file['name']);

        /* See if time/date information has been provided. */
        $ftime = (isset($file['time'])) ? $file['time'] : null;

        /* Get the hex time. */
        $dtime    = dechex($this->_unix2DosTime($ftime));
        $hexdtime = chr(hexdec($dtime[6] . $dtime[7])) .
                    chr(hexdec($dtime[4] . $dtime[5])) .
                    chr(hexdec($dtime[2] . $dtime[3])) .
                    chr(hexdec($dtime[0] . $dtime[1]));

        /* "Local file header" segment. */
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzdeflate($data);
        $c_len   = strlen($zdata);

        /* Common data for the two entries. */
        $common =
            "\x14\x00" .                /* Version needed to extract. */
            "\x00\x00" .                /* General purpose bit flag. */
            "\x08\x00" .                /* Compression method. */
            $hexdtime .                 /* Last modification time/date. */
            pack('V', $crc) .           /* CRC 32 information. */
            pack('V', $c_len) .         /* Compressed filesize. */
            pack('V', $unc_len) .       /* Uncompressed filesize. */
            pack('v', strlen($name)) .  /* Length of filename. */
            pack('v', 0);               /* Extra field length. */

        /* Add this entry to zip data. */
        fseek($this->_tmp, 0, SEEK_END);
        $old_offset = ftell($this->_tmp);

        fwrite($this->_tmp,
            self::FILE_HEADER .  /* Begin creating the ZIP data. */
            $common .            /* Common data. */
            $name .              /* File name. */
            $zdata               /* "File data" segment. */
        );

        /* Add to central directory record. */
        $this->_ctrldir[] =
            self::CTRL_DIR_HEADER .
            "\x00\x00" .              /* Version made by. */
            $common .                 /* Common data. */
            pack('v', 0) .            /* File comment length. */
            pack('v', 0) .            /* Disk number start. */
            pack('v', 0) .            /* Internal file attributes. */
            pack('V', 32) .           /* External file attributes -
                                       * 'archive' bit set. */
            pack('V', $old_offset) .  /* Relative offset of local
                                       * header. */
            $name;                    /* File name. */
    }

}
