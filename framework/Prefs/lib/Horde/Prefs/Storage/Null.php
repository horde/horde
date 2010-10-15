<?php
/**
 * Null storage driver for the preferences system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs_Storage_Null extends Horde_Prefs_Storage
{
    /**
     */
    public function get($scope)
    {
        return false;
    }

    /**
     */
    public function store($prefs)
    {
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
    }

}
