<?php
/**
 * This class provides a filter for mailing list messages.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Filter_Mailinglist extends IMP_Search_Filter_Builtin
{
    /**
     * Initialization tasks.
     */
    protected function _init()
    {
        $this->_id = 'filter_mlist';
        $this->_label = _("Mailing List Messages");

        $this->add(new IMP_Search_Element_Mailinglist());
    }

}
