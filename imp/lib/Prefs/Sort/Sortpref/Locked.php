<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A mailbox sort object that has the sortby value locked.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Sort_Sortpref_Locked extends IMP_Prefs_Sort_Sortpref
{
    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'sortby_locked':
            return true;
        }

        return parent::__get($name);
    }

}
