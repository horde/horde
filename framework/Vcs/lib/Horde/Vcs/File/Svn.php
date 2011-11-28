<?php
/**
 * Subversion file class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_File_Svn extends Horde_Vcs_File_Base
{
    /**
     * @var resource
     */
    public $logpipe;

    protected function _init()
    {
        // This doesn't work; need to find another way to simply
        // request the most recent revision:
        //
        // $flag = $this->_quicklog ? '-r HEAD ' : '';

        $cmd = $this->_rep->getCommand() . ' log -v ' . escapeshellarg($this->getPath()) . ' 2>&1';
        $pipe = popen($cmd, 'r');
        if (!$pipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn log: ' . $cmd);
        }

        $header = fgets($pipe);
        if (!strspn($header, '-')) {
            throw new Horde_Vcs_Exception('Error executing svn log: ' . $header);
        }

        $this->logpipe = $pipe;
        while (!feof($pipe)) {
            try {
                $log = $this->_rep->getLog($this, null);
                $rev = $log->getRevision();
                $this->logs[$rev] = $log;
                $this->_revs[] = $rev;
            } catch (Horde_Vcs_Exception $e) {}

            if ($this->_quicklog) {
                break;
            }
        }

        pclose($pipe);
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v).
     *
     * @return string  Filename without repository extension.
     */
    public function getFileName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }

    /**
     * Returns the revision before the specified revision.
     *
     * @param string $rev  A revision.
     *
     * @return string  The previous revision or null if the first revision.
     */
    public function getPreviousRevision($rev)
    {
        /* Shortcut for SVN's incrementing revisions. */
        $rev--;
        return $rev ? $rev : null;
    }
}
