<?php
/**
 * This class provides a filter for messages sent from addresses contained
 * within a user's personal contacts.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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
