<?php
/**
 * Defines AJAX calls used to interact with Horde preferences.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Application_Helper_Prefs
{
    /**
     * Sets a preference value.
     *
     * Variables used:
     *   - pref: (string) The preference name.
     *   - value: (mixed) The preference value.
     *
     * @return boolean  True on success.
     */
    public function setPrefValue(Horde_Core_Ajax_Application $app_ob)
    {
        return $GLOBALS['prefs']->setValue(
            $app_ob->vars->pref,
            $app_ob->vars->value
        );
    }

}
