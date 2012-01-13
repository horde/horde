<?php
/**
 * Base directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
abstract class Horde_Vcs_Directory_Base
{
    /**
     * The directory's repository object.
     *
     * @var Horde_Vcs_Base
     */
    protected $_rep;

    /**
     * The directory's path inside the repository.
     *
     * @var string
     */
    protected $_dirName;

    /**
     * A list of Horde_Vcs_File_Base objects representing all files inside this
     * directory.
     *
     * @var array
     */
    protected $_files = array();

    /**
     * A (string) list of directories inside this directory.
     *
     * @var array
     */
    protected $_dirs = array();

    /**
     * Constructor.
     *
     * @param Horde_Vcs_Base $rep  A repository object.
     * @param string $dn           Path to the directory.
     * @param array $opts          Any additional options:
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct(Horde_Vcs_Base $rep, $dn, $opts = array())
    {
        $this->_rep = $rep;
        $this->_dirName = '/' . ltrim($dn, '/');
    }

    /**
     * Returns a list of directories inside this directory.
     *
     * return array  A (string) list of directories.
     */
    public function getDirectories()
    {
        return $this->_dirs;
    }

    /**
     * Returns a list of all files inside this directory.
     *
     * @return array  A list of Horde_Vcs_File_Base objects.
     */
    public function getFiles($showdeleted = false)
    {
        return $this->_files;
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
        // Always sort directories by name.
        natcasesort($this->_dirs);

        $this->_doFileSort($this->_files, $how);

        if ($dir == Horde_Vcs::SORT_DESCENDING) {
            $this->_dirs = array_reverse($this->_dirs);
            $this->_files = array_reverse($this->_files);
        }
    }

    /**
     * Sorts a list files.
     *
     * @see applySort()
     *
     * @param array $fileList  A list of files.
     * @param integer $how     A Horde_Vcs::SORT_* constant.
     */
    protected function _doFileSort(&$fileList, $how = Horde_Vcs::SORT_NONE)
    {
        switch ($how) {
        case Horde_Vcs::SORT_AGE:
            usort($fileList, array($this, '_fileAgeSort'));
            break;

        case Horde_Vcs::SORT_NAME:
            usort($fileList, array($this, '_fileNameSort'));
            break;

        case Horde_Vcs::SORT_AUTHOR:
            usort($fileList, array($this, '_fileAuthorSort'));
            break;

        case Horde_Vcs::SORT_REV:
            usort($fileList, array($this, '_fileRevSort'));
            break;

        case Horde_Vcs::SORT_NONE:
        default:
            break;
        }
    }

    /**
     * Sort function for ascending age.
     */
    public function _fileAgeSort($a, $b)
    {
        $aa = $a->getLastLog();
        $bb = $b->getLastLog();
        return ($aa->getDate() == $bb->getDate())
            ? 0
            : (($aa->getDate() < $bb->getDate()) ? 1 : -1);
    }

    /**
     * Sort function by author name.
     */
    public function _fileAuthorSort($a, $b)
    {
        $aa = $a->getLastLog();
        $bb = $b->getLastLog();
        return ($aa->getAuthor() == $bb->getAuthor())
            ? 0
            : (($aa->getAuthor() > $bb->getAuthor()) ? 1 : -1);
    }

    /**
     * Sort function for ascending filename.
     */
    public function _fileNameSort($a, $b)
    {
        return strcasecmp($a->getFileName(), $b->getFileName());
    }

    /**
     * Sort function for ascending revision.
     */
    public function _fileRevSort($a, $b)
    {
        return $this->_rep->cmp($a->getRevision(), $b->getRevision());
    }

    /**
     * Returns a list of all branches in this directory.
     *
     * @return array  A branch list.
     */
    public function getBranches()
    {
        return array();
    }
}
