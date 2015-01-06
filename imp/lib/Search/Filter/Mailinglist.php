<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Filter for mailing list messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Filter_Mailinglist extends IMP_Search_Filter_Builtin
{
    /**
     */
    protected function _init()
    {
        $this->_id = 'filter_mlist';
        $this->_label = _("Mailing List Messages");

        $this->add(new IMP_Search_Element_Mailinglist());
    }

}
