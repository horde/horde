<?php
/**
 * Stub storage driver for the preferences system.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   GunnarWrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs_Stub_Storage extends Horde_Prefs_Storage_Base
{
    /**
     */
    public function get($scope_ob)
    {
        /** Provide dummy pref */
        $scope_ob->set('a', 'b');
        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
    }

}
