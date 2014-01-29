<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Prefs_Sort object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_PrefsSort extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Prefs_Sort instance.
     *
     * @return IMP_Prefs_Sort  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        switch ($GLOBALS['registry']->getView()) {
        case Horde_Registry::VIEW_MINIMAL:
        case Horde_Registry::VIEW_SMARTMOBILE:
            return new IMP_Prefs_Sort_FixedDate();

        default:
            return new IMP_Prefs_Sort();
        }
    }

}
