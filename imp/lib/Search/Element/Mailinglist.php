<?php
/**
 * This class handles the mailing list search query.
 *
 * Uses the List-Id header defined by RFC 2919 to identify mailing list
 * messages.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Element_Mailinglist extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param boolean $not  If true, do a 'NOT' search of $text.
     */
    public function __construct($not = false)
    {
        /* Data element: (integer) Do a NOT search? */
        $this->_data = intval($not);
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->headerText('list-id', '', $this->_data);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return ($this->_data ? _("not") . ' ' : '') . _("Mailing List Messages");
    }

}
