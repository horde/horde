<?php
/**
 * RCS directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Directory_Rcs extends Horde_Vcs_Directory_Base
{
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
        parent::__construct($rep, $dn, $opts);
        $dir = $rep->sourceroot . $this->_dirName;

        /* Make sure we are trying to list a directory */
        if (!@is_dir($dir)) {
            throw new Horde_Vcs_Exception('Unable to find directory: ' . $dir);
        }

        /* Open the directory for reading its contents */
        if (!($handle = @opendir($dir))) {
            throw new Horde_Vcs_Exception(empty($php_errormsg) ? 'Permission denied' : $php_errormsg);
        }

        /* Create two arrays - one of all the files, and the other of all the
         * directories. */
        while (($name = readdir($handle)) !== false) {
            if (($name == '.') || ($name == '..')) {
                continue;
            }

            $path = $dir . '/' . $name;
            if (@is_dir($path)) {
                /* Skip Attic directory. */
                if ($name != 'Attic') {
                    $this->_dirs[] = $name;
                }
            } elseif (@is_file($path) && (substr($name, -2) == ',v')) {
                /* Spawn a new file object to represent this file. */
                $this->_files[] = $rep->getFile(
                    substr($path, strlen($rep->sourceroot), -2));
            }
        }

        /* Close the filehandle; we've now got a list of dirs and files. */
        closedir($handle);
    }
}
