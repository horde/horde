<?php
/**
 * Horde-specific prefs handling.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
 */
class Horde_Prefs_Ui
{
    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        global $prefs, $registry;

        if ($prefs->isDirty('language')) {
            $registry->setLanguageEnvironment($prefs->getValue('language'));
            foreach ($registry->listApps() as $app) {
                if ($registry->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
                    $registry->callAppMethod($app, 'changeLanguage');
                }
            }
        }
    }

}
