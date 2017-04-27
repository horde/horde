<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */

/**
 * This class allows tar files to be read.
 *
 * @author    Michael Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress
 */
class Horde_Compress_Tar extends Horde_Compress_Base
{
    /**
     */
    public $canCompress = true;

    /**
     */
    public $canDecompress = true;

    /**
     * Tar file types.
     *
     * @var array
     */
    protected $_types = array(
        0x0   =>  'Unix file',
        0x30  =>  'File',
        0x31  =>  'Link',
        0x32  =>  'Symbolic link',
        0x33  =>  'Character special file',
        0x34  =>  'Block special file',
        0x35  =>  'Directory',
        0x36  =>  'FIFO special file',
        0x37  =>  'Contiguous file'
    );

    /**
     * Temporary contents for compressing files.
     *
     * @var resource
     */
    protected $_tmp;

    /**
     * @since Horde_Compress 2.2.0
     *
     * @param array $data    The data to compress. Requires an array of
     *                       arrays. Each subarray should contain these
     *                       fields:
     *   - data: (string/resource) The data to compress.
     *   - name: (string) The pathname to the file.
     *   - time: (integer) [optional] The timestamp to use for the file.
     *   - spl: (SplFileInfo) [optional] Complete file information.
     * @param array $params  The parameter array.
     *   - stream: (boolean) If set, return a stream instead of a string.
     *             DEFAULT: Return string
     *
     * @return mixed  The TAR file as either a string or a stream resource.
     */
    public function compress($data, $params = array())
    {
        $this->_tmp = fopen('php://temp', 'r+');

        foreach ($data as $file) {
            /* Split up long file names. */
            $name = str_replace('\\', '/', $file['name']);
            $prefix = '';
            if (strlen($name) > 99) {
                $prefix = $name;
                $name = '';
                if (strlen($prefix) > 154) {
                    $name = substr($prefix, 154);
                    $prefix = substr($prefix, 0, 154);
                }
            }

            /* See if time/date information has been provided. */
            $ftime = (isset($file['time'])) ? $file['time'] : null;

            /* "Local file header" segment. */
            if (is_resource($file['data'])) {
                fseek($file['data'], 0, SEEK_END);
                $length = ftell($file['data']);
            } else {
                $length = strlen($file['data']);
            }

            /* Gather extended information. */
            if (isset($file['spl'])) {
                $isLink = $file['spl']->isLink();
                $link = $isLink ? $this->_getLink($file['spl']) : '';
                if (function_exists('posix_getpwuid')) {
                    $posix = posix_getpwuid($file['spl']->getOwner());
                    $owner = $posix['name'];
                }
                if (function_exists('posix_getgrgid')) {
                    $posix = posix_getgrgid($file['spl']->getGroup());
                    $group = $posix['name'];
                }
            } else {
                $isLink = false;
                $link = $owner = $group = '';
            }

            /* Header data for the file entries. */
            $header =
                pack('a99', $name) . "\0" .                 /* Name. */
                $this->_formatNumber($file, 'getPerms') .   /* Permissions. */
                $this->_formatNumber($file, 'getOwner') .   /* Owner ID. */
                $this->_formatNumber($file, 'getGroup') .   /* Group ID. */
                sprintf("%011o\0", $isLink ? 0 : $length) . /* Size. */
                sprintf("%011o\0", $ftime) .                /* MTime. */
                '        ' .                                /* Checksum. */
                ($isLink ? '1' : '0') .                     /* Type. */
                pack('a99', $link) . "\0" .                 /* Link target. */
                "ustar\0" . "00" .                          /* Magic marker. */
                pack('a31', $owner) . "\0" .                /* Owner name. */
                pack('a31', $group) . "\0" .                /* Group name. */
                pack('a16', '') .                           /* Device numbers. */
                pack('a154', $prefix) . "\0";               /* Name prefix. */
            $header = pack('a512', $header);
            $checksum = array_sum(array_map('ord', str_split($header)));
            $header = substr($header, 0, 148)
                . sprintf("%06o\0 ", $checksum)
                . substr($header, 156);

            /* Add this entry to TAR data. */
            fwrite($this->_tmp, $header);

            /* "File data" segment. */
            if (is_resource($file['data'])) {
                rewind($file['data']);
                stream_copy_to_stream($file['data'], $this->_tmp);
            } else {
                fwrite($this->_tmp, $file['data']);
            }

            /* Add 512 byte block padding. */
            fwrite($this->_tmp, str_repeat("\0", 512 - ($length % 512)));
        }

        /* End of archive. */
        fwrite($this->_tmp, str_repeat("\0", 1024));

        rewind($this->_tmp);

        if (empty($params['stream'])) {
            $out = stream_get_contents($this->_tmp);
            fclose($this->_tmp);
            return $out;
        }

        return $this->_tmp;
    }

    /**
     * Returns the relative path of a symbolic link
     *
     * @param SplFileInfo $spl  An SplFileInfo object.
     *
     * @return string  The relative path of the symbolic link.
     */
    protected function _getLink($spl)
    {
        $ds = DIRECTORY_SEPARATOR;
        $from = explode($ds, rtrim($spl->getPathname(), $ds));
        $to = explode($ds, rtrim($spl->getRealPath(), $ds));
        while (count($from) && count($to) && ($from[0] == $to[0])) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('..' . $ds, count($from)) . implode($ds, $to);
    }

    /**
     * Formats a number from the file information for the TAR format.
     *
     * @param array $file     A file hash from compress() that may include a
     *                        'spl' entry with an .
     * @param string $method  The method of the SplFileInfo object that returns
     *                        the requested number.
     *
     * @return string  The correctly formatted number.
     */
    protected function _formatNumber($file, $method)
    {
        if (isset($file['spl'])) {
            return sprintf("%07o\0", $file['spl']->$method());
        }
        return pack('a8', '');
    }

    /**
     * @return array  Tar file data:
     * <pre>
     * KEY: Position in the array
     * VALUES:
     *   attr - File attributes
     *   data - Raw file contents
     *   date - File modification time
     *   name - Filename
     *   size - Original file size
     *   type - File type
     * </pre>
     *
     * @throws Horde_Compress_Exception
     */
    public function decompress($data, array $params = array())
    {
        $data_len = strlen($data);
        $position = 0;
        $return_array = array();

        while ($position < $data_len) {
            if (version_compare(PHP_VERSION, '5.5', '>=')) {
                $info = @unpack('Z100filename/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/Z8checksum/Ctypeflag/Z100link/Z6magic/Z2version/Z32uname/Z32gname/Z8devmajor/Z8devminor', substr($data, $position));
            } else {
                $info = @unpack('a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/Ctypeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor', substr($data, $position));
            }
            if (!$info) {
                throw new Horde_Compress_Exception(Horde_Compress_Translation::t("Unable to decompress data."));
            }

            $position += 512;
            $contents = substr($data, $position, octdec($info['size']));
            $position += ceil(octdec($info['size']) / 512) * 512;

            if ($info['filename']) {
                $file = array(
                    'attr' => null,
                    'data' => null,
                    'date' => octdec($info['mtime']),
                    'name' => trim($info['filename']),
                    'size' => octdec($info['size']),
                    'type' => isset($this->_types[$info['typeflag']]) ? $this->_types[$info['typeflag']] : null
                );

                if (($info['typeflag'] == 0) ||
                    ($info['typeflag'] == 0x30) ||
                    ($info['typeflag'] == 0x35)) {
                    /* File or folder. */
                    $file['data'] = $contents;

                    $mode = hexdec(substr($info['mode'], 4, 3));
                    $file['attr'] =
                        (($info['typeflag'] == 0x35) ? 'd' : '-') .
                        (($mode & 0x400) ? 'r' : '-') .
                        (($mode & 0x200) ? 'w' : '-') .
                        (($mode & 0x100) ? 'x' : '-') .
                        (($mode & 0x040) ? 'r' : '-') .
                        (($mode & 0x020) ? 'w' : '-') .
                        (($mode & 0x010) ? 'x' : '-') .
                        (($mode & 0x004) ? 'r' : '-') .
                        (($mode & 0x002) ? 'w' : '-') .
                        (($mode & 0x001) ? 'x' : '-');
                }

                $return_array[] = $file;
            }
        }

        return $return_array;
    }

}
