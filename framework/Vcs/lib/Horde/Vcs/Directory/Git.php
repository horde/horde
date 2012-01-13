<?php
/**
 * Git directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Directory_Git extends Horde_Vcs_Directory_Base
{
    /**
     * The current branch.
     *
     * @var string
     */
    protected $_branch;

    /**
     * Constructor.
     *
     * @todo Throw exception if not a valid directory (ls-tree doesn't really
     *       list directory contents, but all objects matching a pattern, so it
     *       returns an empty list when used with non-existant directories.
     *
     * @param Horde_Vcs_Base $rep  A repository object.
     * @param string $dn           Path to the directory.
     * @param array $opts          Any additional options:
     *                             - 'rev': (string) Generate directory list for
     *                               a certain branch or revision.
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct(Horde_Vcs_Base $rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        $this->_branch = empty($opts['rev'])
            ? $rep->getDefaultBranch()
            : $opts['rev'];

        // @TODO See if we have a valid cache of the tree at this revision

        $dir = $this->_dirName;
        if (substr($dir, 0, 1) == '/') {
            $dir = (string)substr($dir, 1);
        }
        if (strlen($dir) && substr($dir, -1) != '/') {
            $dir .= '/';
        }

        list($stream, $result) = $rep->runCommand(
            'ls-tree --full-name ' . escapeshellarg($this->_branch)
            . ' ' . escapeshellarg($dir));

        /* Create two arrays - one of all the files, and the other of all the
         * dirs. */
        while (!feof($result)) {
            $line = rtrim(fgets($result));
            if (!strlen($line)) {
                continue;
            }

            list(, $type, , $file) = preg_split('/\s+/', $line, -1,
                                                PREG_SPLIT_NO_EMPTY);
            $file = preg_replace('/\\\\(\d+)/e', 'chr(0$1)', $file);
            $file = str_replace(array('\\t', '\\n', '\\\\'),
                                array("\t", "\n", '\\'),
                                $file);
            $file = trim($file, '"');
            if ($type == 'tree') {
                $this->_dirs[] = basename($file);
            } else {
                $this->_files[] = $rep->getFile(
                    $file,
                    array('branch' => $this->_branch));
            }
        }
        fclose($result);
        proc_close($stream);
    }

    /**
     * Returns a list of all branches in this directory.
     *
     * @return array  A branch list.
     */
    public function getBranches()
    {
        $blist = array_keys($this->_rep->getBranchList());
        if (!in_array($this->_branch, $blist)) {
            $blist[] = $this->_branch;
        }
        return $blist;
    }
}