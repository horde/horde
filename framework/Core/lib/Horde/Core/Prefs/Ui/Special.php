<?php
/**
 * Base interface for handling 'special' preference types.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
interface Horde_Core_Prefs_Ui_Special
{
    /**
     * Code to run on initialization.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function init(Horde_Core_Prefs_Ui $ui);

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  The HTML code to display on the prefs UI page.
     */
    public function display(Horde_Core_Prefs_Ui $ui);

    /**
     * Handle updating a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preference was updated.
     */
    public function update(Horde_Core_Prefs_Ui $ui);

}
