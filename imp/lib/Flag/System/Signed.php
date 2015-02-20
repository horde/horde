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
 * This class implements the signed message flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_System_Signed
extends IMP_Flag_Base
implements IMP_Flag_Match_Header
{
    /**
     */
    protected $_css = 'flagSignedmsg';

    /**
     */
    protected $_id = 'signed';

    /**
     */
    protected function _getLabel()
    {
        return _("Message is Signed");
    }

    /**
     */
    public function matchHeader(Horde_Mime_Headers $data)
    {
        return (($ctype = $data['Content-Type']) &&
                ($ctype->value == 'multipart/signed'));
    }

}
