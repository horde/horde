<?php
/**
 * Copyright 2006-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with message parsing relating to UI display.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Message_Ui
{
    /**
     * Return a list of "basic" headers w/gettext translations.
     *
     * @return array  Header name -> gettext translation mapping.
     */
    public function basicHeaders()
    {
        return array(
            'date'      =>  _("Date"),
            'from'      =>  _("From"),
            'to'        =>  _("To"),
            'cc'        =>  _("Cc"),
            'bcc'       =>  _("Bcc"),
            'subject'   =>  _("Subject")
        );
    }

}
