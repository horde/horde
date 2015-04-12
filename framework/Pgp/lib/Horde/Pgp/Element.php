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
     * Armor data.
     *
     * @var Horde_Stream
     */
    protected $_data;

    /**
     * End offset.
     *
     * @var integer
     */
    protected $_end;

    /**
     * Start offset.
     *
     * @var integer
     */
    protected $_start;

    /**
     * Creates the element from the first found armor part in the input data.
     *
     * @param string $data  Armored PGP data.
     *
     * @return Horde_Pgp_Element  PGP element object.
     */
    static public function create($data)
    {
        $class = get_called_class();
        if ($data instanceof $class) {
            return $data;
        }

        $data = Horde_Pgp_Armor::create($data);
        foreach ($data as $val) {
            if ($val instanceof $class) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Constructor.
     *
     * @param Horde_Stream $text  Horde_Stream object containing the full
     *                            armor text.
     * @param integer $start      Start offset.
     * @param integer $end        End offset.
     */
    public function __construct(Horde_Stream $text, $start, $end)
    {
        $this->_data = $text;
        $this->_end = $end;
        $this->_start = $start;
    }

    /**
     */
    public function __toString()
    {
        return $this->getFullText();
    }

    /**
     * Returns the full armored text of the element (including headers).
     *
     * @return string  Full text.
     */
    public function getFullText()
    {
        $pos = $this->_data->pos();
        $out = $this->_data->getString(
            $this->_start,
            $this->_end - $this->_start
        );
        $this->_resetPos($pos);

        return $out;
    }

    /**
     * Return only the data part of the element.
     *
     * @return string  Data part.
     */
    abstract public function getData();

    /**
     * Reset the position of the stream.
     *
     * @param integer $pos  Initial position.
     */
    protected function _resetPos($pos)
    {
        if (is_null($pos)) {
            $this->_data->end();
        } else {
            $this->_data->seek($pos, false);
        }
    }

}
