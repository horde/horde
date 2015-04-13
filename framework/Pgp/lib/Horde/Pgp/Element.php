<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * Abstract class representing a PGP data element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
abstract class Horde_Pgp_Element
{
    /**
     * Armor headers.
     *
     * @var array
     */
    public $headers;

    /**
     * Message object.
     *
     * @var OpenPGP_Message
     */
    public $message;

    /**
     * Armor identifier.
     *
     * @var string
     */
    protected $_armor = '';

    /**
     * Creates the element from the first found armor part of the class type
     * in the armored input data.
     *
     * @param mixed $data  Armored PGP data.
     *
     * @return Horde_Pgp_Element  PGP element object.
     */
    static public function create($data)
    {
        $class = get_called_class();
        if ($data instanceof $class) {
            return $data;
        }

        foreach (Horde_Pgp_Armor::create($data) as $val) {
            if ($val instanceof $class) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Constructor.
     *
     * @param mixed $data     Data of the part. Either raw PGP data or a
     *                        OpenPGP_Message object.
     * @param array $headers  Header array.
     */
    public function __construct($data, array $headers = array())
    {
        if (!($data instanceof OpenPGP_Message)) {
            Horde_Pgp_Backend_Openpgp::autoload();
            $data = OpenPGP_Message::parse($data);
        }

        $this->message = $data;
        $this->headers = $headers;
    }

    /**
     */
    public function __toString()
    {
        $bytes = $this->message->to_bytes();

        if (!strlen($this->_armor)) {
            return $bytes;
        }

        return OpenPGP::enarmor(
            $bytes,
            'PGP ' . $this->_armor,
            array_merge($this->headers, array(
                'Version' => 'Horde_Pgp Library (http://www.horde.org/)'
            ))
        );
    }

}
