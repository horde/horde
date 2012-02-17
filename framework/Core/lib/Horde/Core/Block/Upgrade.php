<?php
/**
 * This class allows upgrading portal config preferences from H3 -> H4 format.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Block_Upgrade
{
    /**
     * Upgrades the given preference to H4 format.
     *
     * @param string $name  The preference name.
     */
    public function upgrade($name)
    {
        global $prefs;

        $layout = @unserialize($prefs->getValue($name));
        if (is_array($layout)) {
            $upgrade = false;
        } else {
            $layout = array();
            $upgrade = true;
        }

        foreach (array_keys($layout) as $key) {
            foreach (array_keys($layout[$key]) as $key2) {
                if (isset($layout[$key][$key2]['params']['type'])) {
                    $layout[$key][$key2]['params']['type2'] = $layout[$key][$key2]['app'] . '_Block_' . Horde_String::ucfirst($layout[$key][$key2]['params']['type']);
                    unset($layout[$key][$key2]['params']['type']);

                    $upgrade = true;
                }
            }
        }

        if ($upgrade) {
            $prefs->setValue($name, serialize($layout));
        }
    }

}
