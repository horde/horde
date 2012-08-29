<?php
/**
 * A Horde_Injector based Horde_Core_Topbar factory.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Core_Factory_Topbar extends Horde_Core_Factory_Base
{
    /**
     * Returns a Horde_Core_Topbar instance.
     *
     * @param string $treeRenderer   The name of a Horde_Tree renderer.
     * @param array $rendererParams  Any parameters for the rendere.
     *
     * @return Horde_Core_Topbar  The requested instance.
     * @throws Horde_Exception
     */
    public function create($treeRenderer, $rendererParams)
    {
        return new Horde_Core_Topbar($treeRenderer, $rendererParams);
    }

}
