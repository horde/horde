<?php
/**
 * This class handles size-related search queries.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Search_Element_Size extends IMP_Search_Element
{
    /**
     * Allow NOT search on this element?
     *
     * @var boolean
     */
    public $not = false;

    /**
     * Constructor.
     *
     * @param integer $size    The size (in bytes).
     * @param boolean $larger  Search for messages larger than $size?
     */
    public function __construct($size, $larger = false)
    {
        /* Data element:
         * l = (integer) Larger if non-zero, smaller if zero.
         * s = (integer) Size (in bytes). */
        $this->_data = new stdClass;
        $this->_data->s = intval($size);
        $this->_data->l = intval(!empty($larger));
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->size($this->_data->s, $this->_data->l);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        $label = $this->_data->l
            ? _("Size - Greater Than (KB)")
            : _("Size - Less Than (KB)");

        return $label . ' ' . ($this->_data->s / 1024);
    }

}
