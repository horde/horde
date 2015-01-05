<?php
/**
 * Null cache implementation for the preferences system.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Cache_Null extends Horde_Prefs_Cache_Base
{
    /**
     */
    public function get($scope)
    {
        return false;
    }

    /**
     */
    public function store($scope_ob)
    {
    }

    /**
     */
    public function remove($scope = null)
    {
    }

}
