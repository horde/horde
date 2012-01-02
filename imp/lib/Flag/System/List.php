<?php
/**
 * This class implements the mailing list message flag.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Flag_System_List extends IMP_Flag_System_Match_Header
{
    /**
     */
    protected $_css = 'flagList';

    /**
     */
    protected $_id = 'list';

    /**
     */
    protected function _getLabel()
    {
        return _("Mailing List Message");
    }

    /**
     */
    public function match(Horde_Mime_Headers $data)
    {
        return ($data->getValue('list-post') !== null);
    }

}
