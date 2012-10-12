<?php
/**
 * This class handles header-related search queries.
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
class IMP_Search_Element_Header extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param string $text    The search text.
     * @param string $header  The header field.
     * @param boolean $not    If true, do a 'NOT' search of $text.
     */
    public function __construct($text, $header, $not = false)
    {
        /* Data element:
         * h = (string) Header name (lower case).
         * n = (integer) Do a NOT search?
         * t = (string) The search text. */
        $this->_data = new stdClass;
        $this->_data->h = trim(Horde_String::lower($header));
        $this->_data->n = intval(!empty($not));
        $this->_data->t = $text;
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->headerText($this->_data->h, $this->_data->t, $this->_data->n);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return sprintf(_("%s (Header) for \"%s\""), _(Horde_String::ucfirst($this->_data->h)), ($this->_data->n ? _("not") . ' ' : '') . $this->_data->t);
    }

}
