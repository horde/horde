<?php
/**
 * Horde_Vcs_Cvs directory class.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Cvs extends Horde_Vcs_Directory
{
    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);
        $this->_dirName = $rep->sourceroot() . '/' . $dn;

        /* Make sure we are trying to list a directory */
        if (!@is_dir($this->_dirName)) {
            throw new Horde_Vcs_Exception('Unable to find directory: ' . $this->_dirName);
        }

        /* Open the directory for reading its contents */
        if (!($DIR = @opendir($this->_dirName))) {
            throw new Horde_Vcs_Exception(empty($php_errormsg) ? 'Permission denied' : $php_errormsg);
        }

        /* Create two arrays - one of all the files, and the other of
         * all the directories. */
        while (($name = readdir($DIR)) !== false) {
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
        closedir($DIR);

        /* If we want to merge the attic, add it in here. */
        if (!empty($opts['showattic'])) {
            try {
                $atticDir = new Horde_Vcs_Directory_Cvs($rep, $this->_moduleName . '/Attic', $opts, $this);
                $this->_atticFiles = $atticDir->queryFileList();
                $this->_mergedFiles = array_merge($this->_files, $this->_atticFiles);
            } catch (Horde_Vcs_Exception $e) {}
        }

        return true;
    }

    /**
     * TODO
     */
    public function getBranches()
    {
        return array('HEAD');
    }

}
