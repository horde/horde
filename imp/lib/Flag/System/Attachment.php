<?php
/**
 * This class implements the attachment flag.
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
class IMP_Flag_System_Attachment extends IMP_Flag_System
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
     * @param Horde_Mime_Headers $data  Headers object for a message.
     */
    public function match($data)
    {
        if (!($ctype = $data->getValue('content-type', Horde_Mime_Headers::VALUE_BASE))) {
            return false;
        }

        list($primary, $sub) = explode('/', $ctype, 2);
        return (($primary == 'multipart') &&
            !in_array($sub, array('alternative', 'encrypt', 'related', 'signed')));
    }

}
