<?php
/**
 * This class implements the signed message flag.
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
class IMP_Flag_System_Signed extends IMP_Flag_System_Match_Header
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
    public function match(Horde_Mime_Headers $data)
    {
        return ($data->getValue('content-type', Horde_Mime_Headers::VALUE_BASE) == 'multipart/signed');
    }

}
