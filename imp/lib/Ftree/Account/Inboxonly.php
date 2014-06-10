<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Implementation of the account object for an INBOX-only server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Account_Inboxonly extends IMP_Ftree_Account
{
    /**
     */
    public function getList(array $query = array(), $mask = 0)
    {
        return array(
            array(
                'a' => IMP_Ftree::ELT_IS_SUBSCRIBED,
                'v' => 'INBOX'
            )
        );
    }

    /**
     */
    public function delete(IMP_Ftree_Element $elt)
    {
        return 0;
    }

}
