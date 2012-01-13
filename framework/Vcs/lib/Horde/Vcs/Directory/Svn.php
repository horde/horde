<?php
/**
 * Subversion directory class that stores information about the files in a
 * single directory in the repository.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Directory_Svn extends Horde_Vcs_Directory_Base
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

        $cmd = $rep->getCommand() . ' ls '
            . escapeshellarg($rep->sourceroot . $this->_dirName);

        $dir = proc_open(
            $cmd,
            array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
            $pipes);
        if (!$dir) {
            throw new Horde_Vcs_Exception('Failed to execute svn ls: ' . $cmd);
        }
        if ($error = stream_get_contents($pipes[2])) {
            proc_close($dir);
            throw new Horde_Vcs_Exception($error);
        }

        /* Create two arrays - one of all the files, and the other of all the
         * dirs. */
        $errors = array();
        while (!feof($pipes[1])) {
            $line = chop(fgets($pipes[1], 1024));
            if (!strlen($line)) {
                continue;
            }

            if (substr($line, 0, 4) == 'svn:') {
                $errors[] = $line;
            } elseif (substr($line, -1) == '/') {
                $this->_dirs[] = substr($line, 0, -1);
            } else {
                $this->_files[] = $rep->getFile($this->_dirName . '/' . $line);
            }
        }

        proc_close($dir);
    }
}
