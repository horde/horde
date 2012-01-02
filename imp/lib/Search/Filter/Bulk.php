<?php
/**
 * This class provides a filter for bulk mail.
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
class IMP_Search_Filter_Bulk extends IMP_Search_Filter_Builtin
{
    /**
     */
    protected function _init()
    {
        $this->_id = 'filter_bulk';
        $this->_label = _("Bulk Messages");

        $this->add(new IMP_Search_Element_Bulk());
    }

}
