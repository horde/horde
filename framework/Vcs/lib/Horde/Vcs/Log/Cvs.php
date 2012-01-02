<?php
/**
 * CVS log class.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Log_Cvs extends Horde_Vcs_Log_Rcs
{
    /**
     * Cached branch info.
     *
     * @var string
     */
    protected $_branch;

    /**
     * TODO
     */
    public function setBranch($branch)
    {
        $this->_branch = array($branch);
    }
}
