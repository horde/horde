<?php
/**
 * CVS directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Directory_Cvs extends Horde_Vcs_Directory_Rcs
{
    /**
     * A list of Horde_Vcs_File_Base objects representing all files inside this
     * and any Attic/ sub directory.
     *
     * @var array
     */
    protected $_mergedFiles = array();

    /**
     * Constructor.
     *
     * @param Horde_Vcs_Base $rep  A repository object.
     * @param string $dn           Path to the directory.
     * @param array $opts          Any additional options:
     *                             - 'showattic': (boolean) Parse any Attic/
     *                               sub-directory contents too.
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct(Horde_Vcs_Base $rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        /* If we want to merge the attic, add it in here. */
        if (!empty($opts['showattic'])) {
            try {
                $atticDir = new Horde_Vcs_Directory_Cvs($rep, $dn . '/Attic',
                                                        $opts, $this);
                $this->_mergedFiles = array_merge($this->_files,
                                                  $atticDir->getFiles());
            } catch (Horde_Vcs_Exception $e) {
            }
        }
    }

    /**
     * Returns a list of all files inside this directory.
     *
     * @return array  A list of Horde_Vcs_File_Base objects.
     */
    public function getFiles($showdeleted = false)
    {
        return ($showdeleted && $this->_mergedFiles)
            ? $this->_mergedFiles
            : $this->_files;
    }

    /**
     * Sorts the the directory contents.
     *
     * @param integer $how  A Horde_Vcs::SORT_* constant where * can be:
     *                      NONE, NAME, AGE, REV for sorting by name, age or
     *                      revision.
     * @param integer $dir  A Horde_Vcs::SORT_* constant where * can be:
     *                      ASCENDING, DESCENDING for the order of the sort.
     */
    public function applySort($how = Horde_Vcs::SORT_NONE,
                              $dir = Horde_Vcs::SORT_ASCENDING)
    {
        parent::applySort($how, $dir);

        if (isset($this->_mergedFiles)) {
            $this->_doFileSort($this->_mergedFiles, $how);
            if ($dir == Horde_Vcs::SORT_DESCENDING) {
                $this->_mergedFiles = array_reverse($this->_mergedFiles);
            }
        }
    }

    /**
     * Returns a list of all branches in this directory.
     *
     * @return array  A branch list.
     */
    public function getBranches()
    {
        return array('HEAD');
    }
}
