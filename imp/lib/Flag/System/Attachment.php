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
 * This class implements the attachment flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_System_Attachment
extends IMP_Flag_Base
implements IMP_Flag_Match_Header, IMP_Flag_Match_Order, IMP_Flag_Match_Structure
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
    public function matchOrder()
    {
        return array(
            'IMP_Flag_Match_Structure',
            'IMP_Flag_Match_Header'
        );
    }

    /**
     */
    public function matchHeader(Horde_Mime_Headers $data)
    {
        if ($ctype = $data['Content-Type']) {
            @list($primary, $sub) = explode('/', $ctype->value, 2);
            if (($primary == 'multipart') &&
                !in_array($sub, array('alternative', 'encrypt', 'related', 'signed'))) {
                return true;
            }
        }

        return null;
    }

    /**
     */
    public function matchStructure(Horde_Mime_Part $data)
    {
        foreach ($data->partIterator() as $val) {
            if (IMP_Mime_Attachment::isAttachment($val)) {
                return true;
            }
        }

        return false;
    }

}
