<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class implements the encrypted message flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_System_Encrypted extends IMP_Flag_System_Match_Header
{
    /**
     */
    protected $_css = 'flagEncryptmsg';

    /**
     */
    protected $_id = 'encrypted';

    /**
     */
    protected function _getLabel()
    {
        return _("Message is Encrypted");
    }

    /**
     * @param Horde_Mime_Headers $data
     */
    public function match($data)
    {
        return (($ctype = $data['Content-Type']) &&
                (($ctype->value == 'application/pkcs7-mime') ||
                 ($ctype->value == 'multipart/encrypted')));
    }

}
