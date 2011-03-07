<?php
/**
 * Horde_Vcs_File_Svn class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_File_Svn extends Horde_Vcs_File
{
    /**
     * @var resource
     */
    public $logpipe;

    /**
     * Have we initalized logs and revisions?
     *
     * @var boolean
     */
    private $_initialized = false;

    protected function _ensureRevisionsInitialized()
    {
        if (!$this->_initialized) { $this->_init(); }
        $this->_initialized = true;
    }

    protected function _ensureLogsInitialized()
    {
        if (!$this->_initialized) { $this->_init(); }
        $this->_initialized = true;
    }

    protected function _init()
    {
        // This doesn't work; need to find another way to simply
        // request the most recent revision:
        //
        // $flag = $this->_quicklog ? '-r HEAD ' : '';

        $cmd = $this->_rep->getCommand() . ' log -v ' . escapeshellarg($this->queryFullPath()) . ' 2>&1';
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
                $log = $this->_rep->getLogObject($this, null);
                $rev = $log->queryRevision();
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
    public function queryName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }
}
