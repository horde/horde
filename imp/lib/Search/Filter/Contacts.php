<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Filter for messages sent from addresses contained within a user's personal
 * contacts.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Filter_Contacts extends IMP_Search_Filter_Builtin
{
    /**
     */
    protected function _init()
    {
        $this->_id = 'filter_contacts';
        $this->_label = _("Messages From Personal Contacts");

        $this->add(new IMP_Search_Element_Contacts());
    }

}
