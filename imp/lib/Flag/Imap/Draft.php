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
 * This class implements the draft flag (RFC 3501 [2.3.2]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_Imap_Draft extends IMP_Flag_Imap
{
    /**
     */
    protected $_abbreviation = 'd';

    /**
     */
    protected $_bgcolor = '#9fff25';

    /**
     */
    protected $_css = 'flagDraft';

    /**
     */
    protected $_imapflag = Horde_Imap_Client::FLAG_DRAFT;

    /**
     */
    protected function _getLabel()
    {
        return _("Draft");
    }

}
