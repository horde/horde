<?php
/**
 * This class allows tar files to be read.
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
class Horde_Compress_Tar extends Horde_Compress_Base
{
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
     * Tar file flags.
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
            $info = @unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/Ctypeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor", substr($data, $position));
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
