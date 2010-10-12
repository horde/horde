<?php
/**
 * The Horde_Compress_Rar class allows rar files to be read.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Compress
 */
class Horde_Compress_Rar extends Horde_Compress
{
    /**
     * Rar compression methods
     *
     * @var array
     */
    protected $_methods = array(
        0x30 => 'Store',
        0x31 => 'Fastest',
        0x32 => 'Fast',
        0x33 => 'Normal',
        0x34 => 'Good',
        0x35 => 'Best'
    );

    /**
     * Decompress a rar file and get information from it.
     *
     * @param string $data   The rar file data.
     * @param array $params  The parameter array (Unused).
     *
     * @return array  The uncompressed data:
     * <pre>
     * KEY: Position in RAR archive
     * VALUES:
     *   'attr'    --  File attributes
     *   'date'    --  File modification time
     *   'csize'   --  Compressed file size
     *   'method'  --  Compression method
     *   'name'    --  Filename
     *   'size'    --  Original file size
     * </pre>
     *
     * @throws Horde_Exception
     */
    public function decompress($data, $params = array())
    {
        $blockStart = strpos($data, "\x52\x61\x72\x21\x1a\x07\x00");
        if ($blockStart === false) {
            throw new Horde_Exception($this->_dict->t("Invalid RAR data."));
        }

        $data_len = strlen($data);
        $position = $blockStart + 7;
        $return_array = array();

        while ($position < $data_len) {
            $head_crc = substr($data, $position + 0, 2);
            $head_type = ord(substr($data, $position + 2, 1));
            $head_flags = unpack('vFlags', substr($data, $position + 3, 2));
            $head_flags = $head_flags['Flags'];
            $head_size = unpack('vSize', substr($data, $position + 5, 2));
            $head_size = $head_size['Size'];

            $position += 7;
            $head_size -= 7;

            switch ($head_type) {
            case 0x73:
                /* Archive header */
                $position += $head_size;
                break;

            case 0x74:
                /* File Header */
                $info = unpack('VPacked/VUnpacked/COS/VCRC32/VTime/CVersion/CMethod/vLength/vAttrib', substr($data, $position));

                $return_array[] = array(
                    'name' => substr($data, $position + 25, $info['Length']),
                    'size' => $info['Unpacked'],
                    'csize' => $info['Packed'],
                    'date' => mktime((($info['Time'] >> 11) & 0x1f),
                                     (($info['Time'] >> 5) & 0x3f),
                                     (($info['Time'] << 1) & 0x3e),
                                     (($info['Time'] >> 21) & 0x07),
                                     (($info['Time'] >> 16) & 0x1f),
                                     ((($info['Time'] >> 25) & 0x7f) + 80)),
                    'method' => $this->_methods[$info['Method']],
                    'attr' => (($info['Attrib'] & 0x10) ? 'D' : '-') .
                              (($info['Attrib'] & 0x20) ? 'A' : '-') .
                              (($info['Attrib'] & 0x03) ? 'S' : '-') .
                              (($info['Attrib'] & 0x02) ? 'H' : '-') .
                              (($info['Attrib'] & 0x01) ? 'R' : '-')
                );

                $position += $head_size + $info['Packed'];
                break;

            default:
                $position += $head_size;
                if (isset($add_size)) {
                    $position += $add_size;
                }
                break;
            }
        }

        return $return_array;
    }
}
