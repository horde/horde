<?php
/**
 * This class handles the recipient (To/Cc/Bcc) search query.
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
class IMP_Search_Element_Recipient extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param string $text  The search text.
     * @param boolean $not  If true, do a 'NOT' search of $text.
     */
    public function __construct($text, $not = false)
    {
        /* Data element:
         * n = (integer) Do a NOT search?
         * t = (string) The search text. */
        $this->_data = new stdClass;
        $this->_data->n = intval(!empty($not));
        $this->_data->t = $text;
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $search_ob = new Horde_Imap_Client_Search_Query();
        $and_ob = clone $search_ob;

        $ob = clone $search_ob;
        $ob->headerText('to', $this->_data->t, $this->_data->n);
        $and_ob->orSearch($ob);

        $ob = clone $search_ob;
        $ob->headerText('cc', $this->_data->t, $this->_data->n);
        $and_ob->orSearch($ob);

        $ob = clone $search_ob;
        $ob->headerText('bcc', $this->_data->t, $this->_data->n);
        $and_ob->orSearch($ob);

        $queryob->andSearch($and_ob);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return sprintf(_("Recipients (To/Cc/Bcc) for '%s'"), ($this->_data->n ? _("not") . ' ' : '') . $this->_data->t);
    }

}
