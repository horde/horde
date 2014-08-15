<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Interface for a contacts flag image backend based on the sending host.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts_Flag_Host implements IMP_Contacts_Flag_Backend
{
    /**
     */
    public function flagImg($email)
    {
        $addr = new Horde_Mail_Rfc822_Address($email);
        if ($flag = Horde_Core_Ui_FlagImage::getFlagImageObByHost($addr->host)) {
            return array(
                'desc' => $flag['name'],
                'url' => $flag['ob']
            );
        }

        return null;
    }

}
