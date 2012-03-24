<?php
/**
 * This class handles text-related search queries.
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
class IMP_Search_Element_Text extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param string $text      The search text.
     * @param string $bodyonly  If true, only search in the body of the
     *                          message. If false, also search in the headers.
     * @param boolean $not      If true, do a 'NOT' search of $text.
     */
    public function __construct($text, $bodyonly = true, $not = false)
    {
        /* Data element:
         * b = (integer) Search in body only?
         * n = (integer) Do a NOT search?
         * t = (string) The search text. */
        $this->_data = new stdClass;
        $this->_data->b = intval(!empty($bodyonly));
        $this->_data->n = intval(!empty($not));
        $this->_data->t = $text;
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->text($this->_data->t, $this->_data->b, $this->_data->n);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        $label = $this->_data->b
            ? _("Message Body")
            : _("Entire Message (including Headers)");

        return sprintf(_("%s for '%s'"), $label, ((!empty($this->_data->n)) ? _("not") . ' ' : '') . $this->_data->t);
    }

}
