<?php
/**
 * This class handles an OR clause in a search query.
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
class IMP_Search_Element_Or extends IMP_Search_Element
{
    /**
     */
    public function createQuery($mbox, $queryob)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->orSearch($queryob);

        return $ob;
    }

    /**
     */
    public function queryText()
    {
        return _("OR");
    }

}
