<?php
/**
 * Utility methods to upgrade Horde 3 preference values.
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
class Horde_Core_Prefs_Storage_Upgrade
{
    /**
     * Upgrades the given preferences from the old H3 way of storing
     * serialized data.
     * OLD method: convert charset, serialize, store.
     * NEW method: serialize, convert charset, store.
     *
     * @param Horde_Prefs $prefs_ob  The preferences object.
     * @param array $names           The list of names to upgrade.
     */
    public function upgradeSerialized($prefs_ob, array $names)
    {
        /* Only do upgrade for SQL driver. */
        foreach ($prefs_ob->getStorage() as $storage) {
            if ($storage instanceof Horde_Prefs_Storage_Sql) {
                break;
            }
        }
        if (!($storage instanceof Horde_Prefs_Storage_Sql)) {
            return;
        }

        /* Only do upgrade if charset is not UTF-8. */
        $charset = $storage->getCharset();
        if (strcasecmp($charset, 'UTF-8') === 0) {
            return;
        }

        foreach ($names as $name) {
            if (!$prefs_ob->isDefault($name)) {
                $data = $prefs_ob->getValue($name);

                /* Need to convert only if unserialize fails. If it succeeds,
                 * the data has already been converted or there is no need
                 * to convert. */
                if (@unserialize($data) === false) {
                    /* Re-convert to original charset. */
                    $data = Horde_String::convertCharset($data, 'UTF-8', $charset);

                    /* Unserialize. If we fail here, remove the value
                     * outright since it is invalid and can not be fixed. */
                    if (($data = @unserialize($data)) !== false) {
                        $data = Horde_String::convertCharset($data, $charset, 'UTF-8');

                        /* Re-save in the prefs backend in the new format. */
                        $prefs_ob->setValue($name, serialize($data));
                    }
                }
            }
        }
    }

}
