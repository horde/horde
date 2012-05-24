<?php
/**
 * This class handles the personal recipient search query.
 *
 * This query matches if one of the e-mails defined in the identities
 * matches the To/Cc/Bcc header of an email.
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
class IMP_Search_Element_Personal extends IMP_Search_Element
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
        $search_ob = new Horde_Imap_Client_Search_Query();
        $and_ob = clone $search_ob;
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        foreach ($identity->getAllIdentityAddresses()->bare_addresses as $val) {
            $ob = clone $search_ob;
            $ob->headerText('to', $val, $this->_data);
            $and_ob->orSearch($ob);

            $ob = clone $search_ob;
            $ob->headerText('cc', $val, $this->_data);
            $and_ob->orSearch($ob);

            $ob = clone $search_ob;
            $ob->headerText('bcc', $val, $this->_data);
            $and_ob->orSearch($ob);
        }

        $queryob->andSearch($and_ob);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return ($this->_data ? _("not") . ' ' : '') . _("Personal Messages");
    }

}
