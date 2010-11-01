<?php
/**
 * This class handles size-related search queries.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
        $this->_data->l = intval($larger);
    }

    /**
     * Adds the current query item to the query object.
     *
     * @param Horde_Imap_Client_Search_Query  The query object.
     *
     * @return Horde_Imap_Client_Search_Query  The query object.
     */
    public function createQuery($queryob)
    {
        $queryob->size($this->_data->s, $this->_data->l);

        return $queryob;
    }

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    public function queryText()
    {
        $label = $this->_data->l
            ? _("Size (KB) >")
            : _("Size (KB) <");

        return $label . ' ' . ($rule->v / 1024);
    }

}
