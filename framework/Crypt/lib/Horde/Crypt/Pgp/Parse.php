<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * Provides methods to parse PGP armored text data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
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
     * Metadata names for data.
     */
    const PGP_ARMOR = 'pgp_armor'; /* @since 2.5.0 */
    const SIG_CHARSET = 'pgp_sig_charset';
    const SIG_RAW = 'pgp_sig_raw';

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
            if ((strpos($val, '-----') === 0) &&
                preg_match('/^-----(BEGIN|END) PGP ([^-]+)-----\s*$/', $val, $matches)) {
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

    /**
     * Parses an armored message into a Horde_Mime_Part object.
     *
     * @param mixed $text  Either the text to parse or a Horde_Stream object.
     *
     * @return mixed  Either null if no PGP data was found, or a
     *                Horde_Mime_Part object. For detached signature data:
     *                the full contents of the armored text (data + sig) is
     *                contained in the SIG_RAW metadata, and the charset is
     *                contained in the SIG_CHARSET metadata, within the
     *                application/pgp-signature part.
     */
    public function parseToPart($text, $charset = 'UTF-8')
    {
        $parts = $this->parse($text);

        if (empty($parts) ||
            ((count($parts) == 1) && ($parts[0]['type'] == self::ARMOR_TEXT))) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');

        for ($val = reset($parts); $val; $val = next($parts)) {
            switch ($val['type']) {
            case self::ARMOR_TEXT:
                $part = new Horde_Mime_Part();
                $part->setType('text/plain');
                $part->setCharset($charset);
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case self::ARMOR_PUBLIC_KEY:
                $part = new Horde_Mime_Part();
                $part->setType('application/pgp-keys');
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case self::ARMOR_MESSAGE:
                $part = new Horde_Mime_Part();
                $part->setType('multipart/encrypted');
                $part->setMetadata(self::PGP_ARMOR, true);
                $part->setContentTypeParameter('protocol', 'application/pgp-encrypted');

                $part1 = new Horde_Mime_Part();
                $part1->setType('application/pgp-encrypted');
                $part1->setContents("Version: 1\n");

                $part2 = new Horde_Mime_Part();
                $part2->setType('application/octet-stream');
                $part2->setContents(implode("\n", $val['data']));
                $part2->setDisposition('inline');

                $part->addPart($part1);
                $part->addPart($part2);

                $new_part->addPart($part);
                break;

            case self::ARMOR_SIGNED_MESSAGE:
                if (($sig = next($parts)) &&
                    ($sig['type'] == self::ARMOR_SIGNATURE)) {
                    $part = new Horde_Mime_Part();
                    $part->setType('multipart/signed');
                    // TODO: add micalg parameter
                    $part->setContentTypeParameter('protocol', 'application/pgp-signature');

                    $part1 = new Horde_Mime_Part();
                    $part1->setType('text/plain');
                    $part1->setCharset($charset);

                    $part1_data = implode("\n", $val['data']);
                    $part1->setContents(substr($part1_data, strpos($part1_data, "\n\n") + 2));

                    $part2 = new Horde_Mime_Part();

                    $part2->setType('application/pgp-signature');
                    $part2->setContents(implode("\n", $sig['data']));

                    $part2->setMetadata(self::SIG_CHARSET, $charset);
                    $part2->setMetadata(self::SIG_RAW, implode("\n", $val['data']) . "\n" . implode("\n", $sig['data']));

                    $part->addPart($part1);
                    $part->addPart($part2);
                    $new_part->addPart($part);
                }
            }
        }

        return $new_part;
    }

}
