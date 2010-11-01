<?php
/**
 * Horde_Vcs_Git directory class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Git extends Horde_Vcs_Directory
{
    /**
     * The current branch.
     *
     * @var string
     */
    protected $_branch;

    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository.
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

        $this->_branch = empty($opts['rev'])
            ? $rep->getDefaultBranch()
            : $opts['rev'];

        // @TODO See if we have a valid cache of the tree at this revision

        $dir = $this->queryDir();
        if (substr($dir, 0, 1) == '/') {
            $dir = (string)substr($dir, 1);
        }
        if (strlen($dir) && substr($dir, -1) != '/') {
            $dir .= '/';
        }

        $cmd = $rep->getCommand() . ' ls-tree --full-name ' . escapeshellarg($this->_branch) . ' ' . escapeshellarg($dir) . ' 2>&1';
        $stream = popen($cmd, 'r');
        if (!$stream) {
            throw new Horde_Vcs_Exception('Failed to execute git ls-tree: ' . $cmd);
        }

        // Create two arrays - one of all the files, and the other of
        // all the dirs.
        while (!feof($stream)) {
            $line = fgets($stream);
            if ($line === false) { break; }

            $line = rtrim($line);
            if (!strlen($line))  { continue; }

            list(, $type, , $file) = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            if ($type == 'tree') {
                $this->_dirs[] = basename($file);
            } else {
                $this->_files[] = $rep->getFileObject($file, array('branch' => $this->_branch, 'quicklog' => !empty($opts['quicklog'])));
            }
        }

        pclose($stream);
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