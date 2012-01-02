<?php
/**
 * CVS file class.
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
class Horde_Vcs_File_Cvs extends Horde_Vcs_File_Rcs
{
    /**
     * The current driver.
     *
     * @var string
     */
    protected $_driver = 'Cvs';

    /**
     * If this file is present in an Attic directory, this indicates it.
     *
     * @return boolean  True if file is in the Attic, and false otherwise
     */
    public function isDeleted()
    {
        return (substr($this->_dir, -5) == 'Attic');
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
        return $this->_revlist();
    }

    /**
     * TODO
     */
    public function getTags()
    {
        return $this->_symrev;
    }

    /**
     * TODO
     */
    public function getBranches()
    {
        $this->_ensureInitialized();
        return $this->_branches;
    }
}