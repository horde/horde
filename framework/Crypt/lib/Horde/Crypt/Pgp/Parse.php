<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * Provides method to parse PGP armored text data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 * @since     2.4.0
 */
class Horde_Crypt_Pgp_Parse
{
    /**
     * Armor Header Lines - From RFC 2440:
     *
     * An Armor Header Line consists of the appropriate header line text
     * surrounded by five (5) dashes ('-', 0x2D) on either side of the header
     * line text. The header line text is chosen based upon the type of data
     * that is being encoded in Armor, and how it is being encoded.
     *
     * All Armor Header Lines are prefixed with 'PGP'.
     *
     * The Armor Tail Line is composed in the same manner as the Armor Header
     * Line, except the string "BEGIN" is replaced by the string "END."
     */

    /* Used for signed, encrypted, or compressed files. */
    const ARMOR_MESSAGE = 1;

    /* Used for signed files. */
    const ARMOR_SIGNED_MESSAGE = 2;

    /* Used for armoring public keys. */
    const ARMOR_PUBLIC_KEY = 3;

    /* Used for armoring private keys. */
    const ARMOR_PRIVATE_KEY = 4;

    /* Used for detached signatures, PGP/MIME signatures, and natures
     * following clearsigned messages. */
    const ARMOR_SIGNATURE = 5;

    /* Regular text contained in an PGP message. */
    const ARMOR_TEXT = 6;

    /**
     * Strings in armor header lines used to distinguish between the different
     * types of PGP decryption/encryption.
     *
     * @var array
     */
    protected $_armor = array(
        'MESSAGE' => self::ARMOR_MESSAGE,
        'SIGNED MESSAGE' => self::ARMOR_SIGNED_MESSAGE,
        'PUBLIC KEY BLOCK' => self::ARMOR_PUBLIC_KEY,
        'PRIVATE KEY BLOCK' => self::ARMOR_PRIVATE_KEY,
        'SIGNATURE' => self::ARMOR_SIGNATURE
    );

    /**
     * Parses a message into text and PGP components.
     *
     * @param mixed $text  Either the text to parse or a Horde_Stream object.
     *
     * @return array  An array with the parsed text, returned in blocks of
     *                text corresponding to their actual order. Keys:
     * <pre>
     *   - data: (array) The data for each section. Each line has been
     *           stripped of EOL characters.
     *   - type: (integer) The type of data contained in block. Valid types
     *           are the class ARMOR_* constants.
     * </pre>
     */
    public function parse($text)
    {
        $data = array();
        $temp = array(
            'type' => self::ARMOR_TEXT
        );

        if ($text instanceof Horde_Stream) {
            $stream = $text;
            $stream->rewind();
        } else {
            $stream = new Horde_Stream_Temp();
            $stream->add($text, true);
        }

        while (!$stream->eof()) {
            $val = rtrim($stream->getToChar("\n", false), "\r");
            if (preg_match('/^-----(BEGIN|END) PGP ([^-]+)-----\s*$/', $val, $matches)) {
                if (isset($temp['data'])) {
                    $data[] = $temp;
                }
                $temp = array();

                if ($matches[1] == 'BEGIN') {
                    $temp['type'] = $this->_armor[$matches[2]];
                    $temp['data'][] = $val;
                } elseif ($matches[1] == 'END') {
                    $temp['type'] = self::ARMOR_TEXT;
                    $data[count($data) - 1]['data'][] = $val;
                }
            } else {
                $temp['data'][] = $val;
            }
        }

        if (isset($temp['data']) &&
            ((count($temp['data']) > 1) || !empty($temp['data'][0]))) {
            $data[] = $temp;
        }

        return $data;
    }

}
