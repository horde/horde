<?php
/**
 * The Horde_Perms package provides the Horde permissions system.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Perms
 */
class Horde_Perms
{
    /**
     * Existence of object is known - object is shown to user.
     */
    const SHOW = 2;

    /**
     * Contents of the object can be read.
     */
    const READ = 4;

    /**
     * Contents of the object can be edited.
     */

    const EDIT = 8;

    /**
     * The object can be deleted.
     */
    const DELETE = 16;

    /**
     * A bitmask of all possible permission values.
     *
     * Useful for removeXxxPermission(), unsetPerm(), etc.
     * 30 = SHOW | READ | EDIT | DELETE
     */
    const ALL = 30;

    /**
     * The root permission.
     */
    const ROOT = -1;

    /**
     * Cache for integerToArray().
     *
     * @var array
     */
    static protected $_itaCache = array();

    /**
     * Returns an hash of the available permissions.
     *
     * @return array  The available permissions as a hash.
     */
    static public function getPermsArray()
    {
        return array(
            self::SHOW => Horde_Perms_Translation::t("Show"),
            self::READ => Horde_Perms_Translation::t("Read"),
            self::EDIT => Horde_Perms_Translation::t("Edit"),
            self::DELETE => Horde_Perms_Translation::t("Delete")
        );
    }

    /**
     * Given an integer value of permissions returns an array representation
     * of the integer.
     *
     * @param integer $int  The integer representation of permissions.
     *
     * @return TODO
     */
    static public function integerToArray($int)
    {
        if (isset(self::$_itaCache[$int])) {
            return self::$_itaCache[$int];
        }

        self::$_itaCache[$int] = array();

        /* Get the available perms array. */
        $perms = self::getPermsArray();

        /* Loop through each perm and check if its value is included in the
         * integer representation. */
        foreach ($perms as $val => $label) {
            if ($int & $val) {
                self::$_itaCache[$int][$val] = true;
            }
        }

        return self::$_itaCache[$int];
    }
}
