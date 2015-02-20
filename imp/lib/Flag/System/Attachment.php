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
implements IMP_Flag_Match_Header
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
    public function matchHeader(Horde_Mime_Headers $data)
    {
        if (!($ctype = $data['Content-Type'])) {
            return false;
        }

        @list($primary, $sub) = explode('/', $ctype->value, 2);
        return (($primary == 'multipart') &&
            !in_array($sub, array('alternative', 'encrypt', 'related', 'signed')));
    }

}
