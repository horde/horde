<?php
/**
 * Git directory class that stores information about the files in a single
 * directory in the repository.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
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
     * @param Horde_Vcs_Git $rep  A repository object.
     * @param string $dn          Path to the directory.
     * @param array $opts         TODO
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

        $cmd = $rep->getCommand() . ' ls-tree --full-name '
            . escapeshellarg($this->_branch) . ' ' . escapeshellarg($dir);
        $stream = proc_open(
            $cmd,
            array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
            $pipes);
        if (!$stream) {
            throw new Horde_Vcs_Exception('Failed to execute git ls-tree: ' . $cmd);
        }
        if ($error = stream_get_contents($pipes[2])) {
            proc_close($stream);
            throw new Horde_Vcs_Exception($error);
        }

        /* Create two arrays - one of all the files, and the other of all the
         * dirs. */
        while (!feof($pipes[1])) {
            $line = rtrim(fgets($pipes[1]));
            if (!strlen($line)) {
                continue;
            }

            list(, $type, , $file) = preg_split('/\s+/', $line, -1,
                                                PREG_SPLIT_NO_EMPTY);
            if ($type == 'tree') {
                $this->_dirs[] = basename($file);
            } else {
                $this->_files[] = $rep->getFileObject(
                    $file,
                    array('branch' => $this->_branch,
                          'quicklog' => !empty($opts['quicklog'])));
            }
        }

        proc_close($stream);
    }

    /**
     * TODO
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