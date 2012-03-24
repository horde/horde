<?php
/**
 * This class handles the bulk message search query.
 *
 * Precedence is a non-standard, discouraged header pursuant to RFC 2076
 * [3.9]. However, it is widely used and may be useful in sorting out
 * unwanted e-mail.
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
class IMP_Search_Element_Bulk extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param boolean $not  If true, do a 'NOT' search of $text.
     */
    public function __construct($not = false)
    {
        /* Data element: (integer) Do a NOT search? */
        $this->_data = intval(!empty($not));
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->headerText('precedence', 'bulk', $this->_data);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return ($this->_data ? _("not") . ' ' : '') . _("Bulk Messages");
    }

}
