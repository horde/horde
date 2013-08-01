<?php
/**
 * Stub storage driver for the preferences system.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   GunnarWrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
