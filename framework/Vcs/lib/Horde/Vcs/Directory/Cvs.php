<?php
/**
 * CVS directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2000-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Directory_Cvs extends Horde_Vcs_Directory_Base
{
    /**
     * @var array
     */
    protected $_mergedFiles = array();

    /**
     * Constructor.
     *
     * @param Horde_Vcs_Cvs $rep  A repository object.
     * @param string $dn          Path to the directory.
     * @param array $opts         TODO
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct(Horde_Vcs_Base $rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);
        $this->_dirName = $rep->sourceroot() . '/' . $dn;

        /* Make sure we are trying to list a directory */
        if (!@is_dir($this->_dirName)) {
            throw new Horde_Vcs_Exception('Unable to find directory: ' . $this->_dirName);
        }

        /* Open the directory for reading its contents */
        if (!($dir = @opendir($this->_dirName))) {
            throw new Horde_Vcs_Exception(empty($php_errormsg) ? 'Permission denied' : $php_errormsg);
        }

        /* Create two arrays - one of all the files, and the other of all the
         * directories. */
        while (($name = readdir($dir)) !== false) {
            if (($name == '.') || ($name == '..')) {
                continue;
            }

            $path = $this->_dirName . '/' . $name;
            if (@is_dir($path)) {
                /* Skip Attic directory. */
                if ($name != 'Attic') {
                    $this->_dirs[] = $name;
                }
            } elseif (@is_file($path) && (substr($name, -2) == ',v')) {
                /* Spawn a new file object to represent this file. */
                $this->_files[] = $rep->getFileObject(substr($path, strlen($rep->sourceroot()), -2), array('quicklog' => !empty($opts['quicklog'])));
            }
        }

        /* Close the filehandle; we've now got a list of dirs and files. */
        closedir($dir);

        /* If we want to merge the attic, add it in here. */
        if (!empty($opts['showattic'])) {
            try {
                $atticDir = new Horde_Vcs_Directory_Cvs($rep, $this->_moduleName . '/Attic', $opts, $this);
                $this->_mergedFiles = array_merge($this->_files, $atticDir->queryFileList());
            } catch (Horde_Vcs_Exception $e) {}
        }
    }

    /**
     * TODO
     */
    public function queryFileList($showattic = false)
    {
        return ($showattic && $this->_mergedFiles)
            ? $this->_mergedFiles
            : $this->_files;
    }

    /**
     * Sorts the the directory contents.
     *
     * @param integer $how  Of the form Horde_Vcs::SORT_[*] where * can be:
     *                      NONE, NAME, AGE, REV for sorting by name, age or
     *                      revision.
     * @param integer $dir  Of the form Horde_Vcs::SORT_[*] where * can be:
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
     * TODO
     */
    public function getBranches()
    {
        return array('HEAD');
    }
}
