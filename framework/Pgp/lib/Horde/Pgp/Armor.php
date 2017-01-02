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
 * @package   Pgp
 */

/**
 * Parse PGP armored text data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Armor
implements Countable, SeekableIterator
{
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
        return ($data instanceof Horde_Pgp_Armor)
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
        $base64 = true;
        $class = $end = $end_armor = $ob_class = $start = null;
        $headers = array();
        $stream = $this->_data;

        while (!($eof = $stream->eof())) {
            $pos = $stream->pos();
            $val = rtrim($stream->getToChar("\n", !is_null($start)));

            if (is_null($end_armor) &&
                (strpos($val, '-----BEGIN PGP ') === 0) &&
                (substr($val, -5) === '-----')) {
                $armor = substr($val, 15, strpos($val, '-', 15) - 15);
                if ($start) {
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
                    $base64 = false;
                    $class = 'Horde_Pgp_Element_SignedMessage';
                    break;

                default:
                    /* Unknown: ignore. */
                    continue 2;
                }

                $end_armor = '-----END PGP ' . $armor . '-----';
            } elseif (!is_null($end_armor)) {
                if (is_null($start)) {
                    if (strlen($val)) {
                        list($h, $v) = explode(':', $val, 2);
                        $headers[trim($h)] = trim($v);
                    } else {
                        $start = $stream->pos();
                    }
                } elseif ($val === $end_armor) {
                    $end = $pos;
                    $ob_class = $class;
                    break;
                }
            }
        }

        if ($eof && (is_null($this->_key) || is_null($start))) {
            $this->_current = $this->_key = null;
            return;
        }

        if (is_null($ob_class)) {
            $ob_class = 'Horde_Pgp_Element_Text';
        }

        $pos = $stream->pos();
        $data = $stream->getString($start, $end - 1);
        $stream->seek($pos, false);

        if ($base64) {
            /* Get checksum, if it exists. */
            if ($pos = strrpos($data, "\n=")) {
                $checksum = base64_decode(substr($data, $pos + 2, 4));
                $data = base64_decode(substr($data, 0, $pos));

                Horde_Pgp_Backend_Openpgp::autoload();
                $data_checksum = substr(pack('N', OpenPGP::crc24($data)), 1);
                if ($data_checksum !== $checksum) {
                    // Checksum error!
                    return $this->next();
                }
            } else {
                $data = base64_decode($data);
            }
        }

        $this->_current = new $ob_class($data, $headers);
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
