<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class implements the junk flag.
 * See: http://www.ietf.org/mail-archive/web/morg/current/msg00441.html
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_Imap_Junk extends IMP_Flag_Imap
{
    /**
     */
    protected $_abbreviation = 'J';

    /**
     */
    protected $_css = 'flagJunk';

    /**
     */
    protected $_imapflag = '$junk';

    /**
     */
    protected function _getLabel()
    {
        return _("Junk");
    }

}
