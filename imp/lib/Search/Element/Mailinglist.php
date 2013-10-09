<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handles the mailing list search query.
 *
 * Uses the List-Id header defined by RFC 2919 to identify mailing list
 * messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        $this->_data = intval(!empty($not));
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
