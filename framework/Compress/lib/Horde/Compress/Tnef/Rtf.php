<?php
/**
 * Object to parse and represent vTOOD data encapsulated in a TNEF file.
 *
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Tnef_Rtf extends Horde_Compress_Tnef_Object
{

    const UNCOMPRESSED = 0x414c454d;
    const COMPRESSED   = 0x75465a4c;

    public $content = '';
    public $size = 0;

    public function __construct($logger, $data)
    {
        parent::__construct($logger, $data);
        $this->_decode();
    }

    protected function _decode()
    {
        $c_size = $this->_geti($this->_data, 32);
        $this->size = $this->_geti($this->_data, 32);
        $magic = $this->_geti($this->_data, 32);
        $crc = $this->_geti($this->_data, 32);

        $this->_logger->debug(sprintf(
            'TNEF: compressed size: %s, size: %s, magic: %s, CRC: %s',
            $c_size, $this->size, $magic, $crc)
        );

        switch ($magic) {
        case self::COMPRESSED:
            $this->_decompress();
            break;
        case self::UNCOMPRESSED:
            $this->content = $this->_data;
            break;
        default:
            $this->_logger->notice('TNEF: Unknown RTF compression.');
        }
    }

    /**
     * Decompress compressed RTF. Logic taken and adapted from NasMail RTF
     * plugin.
     *
     * @return string
     */
    protected function _decompress()
    {
        $uncomp = '';
        $in = $out = $flags = $flag_count = 0;

        $preload = "{\\rtf1\\ansi\\mac\\deff0\\deftab720{\\fonttbl;}{\\f0\\fnil \\froman \\fswiss \\fmodern \\fscript \\fdecor MS Sans SerifSymbolArialTimes New RomanCourier{\\colortbl\\red0\\green0\\blue0\n\r\\par \\pard\\plain\\f0\\fs20\\b\\i\\u\\tab\\tx";
        $length_preload = strlen($preload);

        for ($cnt = 0; $cnt < $length_preload; $cnt++) {
            $uncomp .= $preload{$cnt};
            ++$out;
        }

        // FIXME: document me
        while ($out < ($this->size + $length_preload)) {
            if (($flag_count++ % 8) == 0) {
                $flags = ord($this->_data{$in++});
            } else {
                $flags = $flags >> 1;
            }

            if (($flags & 1) != 0) {
                $offset = ord($this->_data{$in++});
                $length = ord($this->_data{$in++});
                $offset = ($offset << 4) | ($length >> 4);
                $length = ($length & 0xF) + 2;
                $offset = ((int)($out / 4096)) * 4096 + $offset;
                if ($offset >= $out) {
                    $offset -= 4096;
                }
                $end = $offset + $length;
                while ($offset < $end) {
                    $uncomp.= $uncomp[$offset++];
                    ++$out;
                }
            } else {
                $uncomp .= $this->_data{$in++};
                ++$out;
            }
        }
        $this->content = substr_replace($uncomp, "", 0, $length_preload);
    }

    /**
     * Allow this object to set any TNEF attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $attribute  The attribute descriptor.
     * @param mixed $value        The value from the MAPI stream.
     * @param integer $size       The byte length of the data, as reported by
     *                            the MAPI data.
     */
    public function setTnefAttribute($attribute, $value, $size)
    {
    }

    /**
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     */
    public function setMapiAttribute($type, $name, $value)
    {

    }

    /**
     * Output the data for this object in an array.
     *
     * @return array
     *   - type: (string)    The MIME type of the content.
     *   - subtype: (string) The MIME subtype.
     *   - name: (string)    The filename.
     *   - stream: (string)  The file data.
     */
    public function toArray()
    {
        return array(
            'type'    => 'application',
            'subtype' => 'rtf',
            'name'    => 'Untitled.rtf',
            'stream'  => $this->content
        );
    }

}