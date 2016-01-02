<?php
/**
 * Copyright 2002-2016 Horde LLC (http://www.horde.org/)
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
 * Object to parse RTF data encapsulated in a TNEF file.
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

    /**
     * RTF content.
     *
     * @var string
     */
    protected $_content = '';

    /**
     * Size of RTF content.
     *
     * @var integer
     */
    protected $_size = 0;

    /**
     * MIME type.
     *
     * @var string
     */
    public $type = 'application/rtf';

    public function __construct($logger, $data)
    {
        parent::__construct($logger, $data);
        $this->_decode();
    }

    public function __get($property)
    {
        if ($property == 'content') {
            return $this->_content;
        }

        throw new InvalidArgumentException('Invalid property access.');
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
            'stream'  => $this->_content
        );
    }

    /**
     * Obtain a good-enough-for-our-needs plain text representation of
     * the RTF document.
     *
     * @return string The plaintext.
     */
    public function toPlain()
    {
        return $this->_rtf2text($this->_content);
    }

    protected function _decode()
    {
        $c_size = $this->_geti($this->_data, 32);
        $this->_size = $this->_geti($this->_data, 32);
        $magic = $this->_geti($this->_data, 32);
        $crc = $this->_geti($this->_data, 32);

        $this->_logger->debug(sprintf(
            'TNEF: compressed size: %s, size: %s, magic: %s, CRC: %s',
            $c_size, $this->_size, $magic, $crc)
        );

        switch ($magic) {
        case self::COMPRESSED:
            $this->_decompress();
            break;
        case self::UNCOMPRESSED:
            $this->_content = $this->_data;
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

        while ($out < ($this->_size + $length_preload)) {
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
        $this->_content = substr_replace($uncomp, "", 0, $length_preload);
    }

    /**
     * Parse RTF data and return the best plaintext representation we can.
     * Adapted from:
     * http://webcheatsheet.com/php/reading_the_clean_text_from_rtf.php
     *
     * @param string $text  The RTF text.
     *
     * @return string   The plaintext.
     */
    protected function _rtf2text($text)
    {
        $document = '';
        $stack = array();
        $j = -1;

        // Read the data character-by- character…
        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $c = $text[$i];
            switch ($c) {
            case '\\':
                // Key Word
                $nextCharacter = $text[$i + 1];

                // If it is another backslash or nonbreaking space or hyphen,
                // then the character is plain text and add it to the output stream.
                if ($nextCharacter == '\\' && $this->_rtfIsPlain($stack[$j])) {
                    $document .= '\\';
                } elseif ($nextCharacter == '~' && $this->_rtfIsPlain($stack[$j])) {
                    $document .= ' ';
                } elseif ($nextCharacter == '_' && $this->_rtfIsPlain($stack[$j])) {
                    $document .= '-';
                } elseif ($nextCharacter == '*') {
                    // Add to the stack.
                    $stack[$j]['*'] = true;
                } elseif ($nextCharacter == "'") {
                    // If it is a single quote, read next two characters that
                    // are the hexadecimal notation of a character we should add
                    // to the output stream.
                    $hex = substr($text, $i + 2, 2);
                    if ($this->_rtfIsPlain($stack[$j])) {
                        $document .= html_entity_decode('&#' . hexdec($hex) .';');
                    }
                    //Shift the pointer.
                    $i += 2;
                } elseif ($nextCharacter >= 'a' && $nextCharacter <= 'z'
                          || $nextCharacter >= 'A' && $nextCharacter <= 'Z') {
                    // Since, we’ve found the alphabetic character, the next
                    // characters are control words and, possibly, some digit
                    // parameter.
                    $word = '';
                    $param = null;
                    // Start reading characters after the backslash.
                    for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
                        $nextCharacter = $text[$k];
                        // If the current character is a letter and there were
                        // no digits before it, then we’re still reading the
                        // control word. If there were digits, we should stop
                        // since we reach the end of the control word.
                        if ($nextCharacter >= 'a' && $nextCharacter <= 'z'
                            || $nextCharacter >= 'A' && $nextCharacter <= 'Z') {
                            if (!empty($param)) {
                                break;
                            }
                            $word .= $nextCharacter;
                        } elseif ($nextCharacter >= '0' && $nextCharacter <= '9') {
                            // If it is a digit, store the parameter.
                            $param .= $nextCharacter;
                        } elseif ($nextCharacter == '-') {
                            // Since minus sign may occur only before a digit
                            // parameter, check whether $param is empty.
                            // Otherwise, we reach the end of the control word.
                            if (!empty($param)) {
                                break;
                            }
                            $param .= $nextCharacter;
                        } else {
                            break;
                        }
                    }

                    // Shift the pointer on the number of read characters.
                    $i += $m - 1;

                    // Start analyzing.We are interested mostly in control words
                    $toText = '';
                    switch (Horde_String::lower($word)) {
                    // If the control word is "u", then its parameter is
                    // the decimal notation of the Unicode character that
                    // should be added to the output stream. We need to
                    // check whether the stack contains \ucN control word.
                    // If it does, we should remove the N characters from
                    // the output stream.
                    case 'u':
                        $toText .= html_entity_decode('&#x' . dechex($param) .';');
                        $ucDelta = @$stack[$j]['uc'];
                        if ($ucDelta > 0) {
                            $i += $ucDelta;
                        }
                        break;
                    case 'par':
                    case 'page':
                    case 'column':
                    case 'line':
                    case 'lbr':
                        $toText .= "\n";
                        break;
                    case 'emspace':
                    case 'enspace':
                    case 'qmspace':
                        $toText .= ' ';
                        break;
                    case 'tab':
                        $toText .= "\t";
                        break;
                    case 'chdate':
                        $toText .= date('m.d.Y');
                        break;
                    case 'chdpl':
                        $toText .= date('l, j F Y');
                        break;
                    case 'chdpa':
                        $toText .= date('D, j M Y');
                        break;
                    case 'chtime':
                        $toText .= date('H:i:s');
                        break;
                    case 'emdash':
                        $toText .= html_entity_decode('&mdash;');
                        break;
                    case 'endash':
                        $toText .= html_entity_decode('&ndash;');
                        break;
                    case 'bullet':
                        $toText .= html_entity_decode('&#149;');
                        break;
                    case 'lquote':
                        $toText .= html_entity_decode('&lsquo;');
                        break;
                    case 'rquote':
                        $toText .= html_entity_decode('&rsquo;');
                        break;
                    case 'ldblquote':
                        $toText .= html_entity_decode('&laquo;');
                        break;
                    case 'rdblquote':
                        $toText .= html_entity_decode('&raquo;');
                        break;
                    default:
                        $stack[$j][Horde_String::lower($word)] = empty($param) ? true : $param;
                        break;
                    }
                    // Add data to the output stream if required.
                    if ($this->_rtfIsPlain($stack[$j])) {
                        $document .= $toText;
                    }
                }
                $i++;
                break;
            case '{':
                // New subgroup starts, add new stack element and write the data
                // from previous stack element to it.
                if (!empty($stack[$j])) {
                    array_push($stack, $stack[$j++]);
                } else {
                    $j++;
                }
                break;
            case '}':
                array_pop($stack);
                $j--;
                break;
            case '\0':
            case '\r':
            case '\f':
            case '\n':
                // Junk
                break;
            default:
                // Add other data to the output stream if required.
                if (!empty($stack[$j]) && $this->_rtfIsPlain($stack[$j])) {
                    $document .= $c;
                }
                break;
            }
        }

        return $document;
    }

    protected function _rtfIsPlain($s)
    {
        $notPlain = array('*', 'fonttbl', 'colortbl', 'datastore', 'themedata', 'stylesheet');
        for ($i = 0; $i < count($notPlain); $i++) {
            if (!empty($s[$notPlain[$i]])) {
                return false;
            }
        }
        return true;
    }

}