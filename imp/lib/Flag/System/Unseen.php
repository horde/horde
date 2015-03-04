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
 * This class implements formatting for unseen messages. Unseen occurs when
 * the seen flag (RFC 3501 [2.3.2]) is NOT set; thus, it can not be handled
 * in the seen flag object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_System_Unseen
extends IMP_Flag_Base
implements IMP_Flag_Match_Flag
{
    /**
     */
    protected $_bgcolor = '#eef';

    /**
     */
    protected $_css = 'flagUnseen';

    /**
     */
    protected $_id = 'unseen';

    /**
     */
    protected function _getLabel()
    {
        return _("Unseen");
    }

    /**
     */
    public function changed($obs, $add)
    {
        foreach ($obs as $val) {
            if ($val instanceof IMP_Flag_Imap_Seen) {
                return !$add;
            }
        }

        return null;
    }

    /**
     */
    public function matchFlag(array $data)
    {
        return !in_array(Horde_Imap_Client::FLAG_SEEN, $data);
    }

}
