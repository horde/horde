<?php
/**
 * Horde_Vcs_Directory_Svn class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Svn extends Horde_Vcs_Directory
{
    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository.
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        $cmd = $rep->getCommand() . ' ls ' . escapeshellarg($rep->sourceroot() . $this->queryDir()) . ' 2>&1';

        $dir = popen($cmd, 'r');
        if (!$dir) {
            throw new Horde_Vcs_Exception('Failed to execute svn ls: ' . $cmd);
        }

        /* Create two arrays - one of all the files, and the other of
         * all the dirs. */
        $errors = array();
        while (!feof($dir)) {
            $line = chop(fgets($dir, 1024));
            if (!strlen($line)) {
                continue;
            }

            if (substr($line, 0, 4) == 'svn:') {
                $errors[] = $line;
            } elseif (substr($line, -1) == '/') {
                $this->_dirs[] = substr($line, 0, -1);
            } else {
                $this->_files[] = $rep->getFileObject($this->queryDir() . '/' . $line, array('quicklog' => !empty($opts['quicklog'])));
            }
        }

        pclose($dir);

        if (empty($errors)) {
            return true;
        }

        throw new Horde_Vcs_Exception(implode("\n", $errors));
    }
}
