<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * Parse PGP armored text data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Armor
implements Countable, SeekableIterator
{
    /**
     * Horde_Mime_Part metadata keys.
     */
    const ARMOR = 'pgp_armor';
    const SIG_CHARSET = 'pgp_sig_charset';
    const SIG_RAW = 'pgp_sig_raw';

    /**
     * Current element for iterator.
     *
     * @var Horde_Pgp_Element
     */
    protected $_current;

    /**
     * Armor data.
     *
     * @var Horde_Stream
     */
    protected $_data;

    /**
     * Current key for iterator.
     *
     * @var integer
     */
    protected $_key;

    /**
     * Create an armor object from input, if not already an armor object.
     *
     * @param mixed $data  Input data.
     *
     * @return Horde_Pgp_Armor  Armor object.
     */
    static public function create($data)
    {
        return ($data instanceof Horde_Crypt_Pgp)
            ? $data
            : new self($data);
    }

    /**
     * Constructor.
     *
     * @param mixed $text  Either the text to parse or a Horde_Stream object.
     */
    public function __construct($data)
    {
        if ($data instanceof Horde_Stream) {
            $this->_data = $data;
        } else {
            $this->_data = new Horde_Stream_Temp();
            $this->_data->add($data, true);
        }
    }

    /**
     * Parses an armored message into a Horde_Mime_Part object.
     *
     * @param string $charset  Charset of the embedded text.
     *
     * @return mixed  Either null if no PGP data was found, or a
     *                Horde_Mime_Part object. For detached signature data:
     *                the full contents of the armored text (data + sig) is
     *                contained in the SIG_RAW metadata, and the charset is
     *                contained in the SIG_CHARSET metadata, within the
     *                application/pgp-signature part.
     */
    public function parseToPart($charset = 'UTF-8')
    {
        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');

        $pgp_data = false;

        foreach ($this as $key => $val) {
            switch ($val['type']) {
            //case self::ARMOR_TEXT:
            case 1:
                $part = new Horde_Mime_Part();
                $part->setType('text/plain');
                $part->setCharset($charset);
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                if ($key) {
                    $pgp_data = true;
                }
                break;

            //case self::ARMOR_PUBLIC_KEY:
            case 2:
                $part = new Horde_Mime_Part();
                $part->setType('application/pgp-keys');
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                $pgp_data = true;
                break;

            case 3:
            //case self::ARMOR_MESSAGE:
                $part = new Horde_Mime_Part();
                $part->setType('multipart/encrypted');
                $part->setMetadata(self::ARMOR, true);
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
                $pgp_data = true;
                break;

            case 4:
            //case self::ARMOR_SIGNED_MESSAGE:
                if (($sig = current($parts)) &&
                    //($sig['type'] == self::ARMOR_SIGNATURE)) {
                    ($sig['type'] == 5)) {
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

                    next($parts);
                }
                $pgp_data = true;
                break;
            }
        }

        return $pgp_data ? null : $new_part;
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return count(iterator_to_array($this));
    }

    /* SeekableIterator methods. */

    /**
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     */
    public function next()
    {
        $class = $end_armor = $ob_class = $start = null;
        $line_read = false;
        $stream = $this->_data;

        while (!($eof = $stream->eof())) {
            $pos = $stream->pos();
            $val = rtrim($stream->getToChar("\n"));

            if (is_null($start) &&
                (strpos($val, '-----BEGIN PGP ') === 0) &&
                (substr($val, -5) === '-----')) {
                $armor = substr($val, 15, strpos($val, '-', 15) - 15);
                if ($line_read) {
                    $stream->seek($pos, false);
                    break;
                }

                switch ($armor) {
                case 'MESSAGE':
                    $class = 'Horde_Pgp_Element_Message';
                    break;

                case 'PUBLIC KEY BLOCK':
                    $class = 'Horde_Pgp_Element_PublicKey';
                    break;

                case 'PRIVATE KEY BLOCK':
                    $class = 'Horde_Pgp_Element_PrivateKey';
                    break;

                case 'SIGNATURE':
                    $class = 'Horde_Pgp_Element_Signature';
                    break;

                case 'SIGNED MESSAGE':
                    $armor = 'SIGNATURE';
                    $class = 'Horde_Pgp_Element_SignedMessage';
                    break;

                default:
                    /* Unknown: ignore. */
                    continue 2;
                }

                $end_armor = '-----END PGP ' . $armor . '-----';
                $start = $pos;
            } elseif (!is_null($end_armor) && ($val === $end_armor)) {
                $ob_class = $class;
                break;
            }

            /* strlen() test ignores empty space before/after armor. */
            if (strlen($val)) {
                $line_read = true;
            }
        }

        if ($eof && (is_null($this->_key) || !$line_read)) {
            $this->_current = $this->_key = null;
            return;
        }

        if (is_null($ob_class)) {
            $ob_class = 'Horde_Pgp_Element_Text';
        }

        $this->_current = new $ob_class($stream, $start, $stream->pos());
        $this->_key = is_null($this->_key)
            ? 0
            : $this->_key + 1;
    }

    /**
     */
    public function rewind()
    {
        $this->_data->rewind();
        $this->_current = $this->_key = null;

        $this->next();
    }

    /**
     */
    public function valid()
    {
        return !is_null($this->_key);
    }

    /**
     */
    public function seek($position)
    {
        $this->rewind();
        while ($position--) {
            $this->next();
        }
        if (!$this->valid()) {
            throw new OutOfBoundsException();
        }
    }

}
