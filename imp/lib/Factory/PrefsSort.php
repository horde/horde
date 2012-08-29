<?php
/**
 * A Horde_Injector based factory for the IMP_Prefs_Sort object.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Prefs_Sort object.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
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
            return new IMP_Prefs_Sort_Fixed();

        default:
            return new IMP_Prefs_Sort();
        }
    }

}
