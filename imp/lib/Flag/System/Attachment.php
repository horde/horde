<?php
/**
 * This class implements the attachment flag.
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
class IMP_Flag_System_Attachment extends IMP_Flag_System_Match_Header
{
    /**
     */
    protected $_css = 'flagAttachmsg';

    /**
     */
    protected $_id = 'attach';

    /**
     */
    protected function _getLabel()
    {
        return _("Message has Attachments");
    }

    /**
     */
    public function match(Horde_Mime_Headers $data)
    {
        if (!($ctype = $data->getValue('content-type', Horde_Mime_Headers::VALUE_BASE))) {
            return false;
        }

        @list($primary, $sub) = explode('/', $ctype, 2);
        return (($primary == 'multipart') &&
            !in_array($sub, array('alternative', 'encrypt', 'related', 'signed')));
    }

}
