<?php
/**
 * This class implements the deleted flag (RFC 3501 [2.3.2]).
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
class IMP_Flag_Imap_Deleted extends IMP_Flag_Imap
{
    /**
     */
    protected $_abbreviation = 'D';

    /**
     */
    protected $_bgcolor = '#999';

    /**
     */
    protected $_css = 'flagDeleted';

    /**
     */
    protected $_imapflag = '\\deleted';

    /**
     */
    protected function _getLabel()
    {
        return _("Deleted");
    }

}
