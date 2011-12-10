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
     * The current driver.
     *
     * @var string
     */
    protected $_driver = 'Svn';

    /**
     * @var resource
     */
    protected $_logpipe;

    protected function _init()
    {
        $cmd = $this->_rep->getCommand() . ' log -v '
            . escapeshellarg($this->getPath()) . ' 2>&1';
        $this->_logpipe = popen($cmd, 'r');
        if (!$this->_logpipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn log: ' . $cmd);
        }

        $header = fgets($this->_logpipe);
        if (!strspn($header, '-')) {
            throw new Horde_Vcs_Exception('Error executing svn log: ' . $header);
        }

        while (!feof($this->_logpipe)) {
            try {
                $log = $this->_getLog();
                $rev = $log->getRevision();
                $this->_logs[$rev] = $log;
                $this->_revs[] = $rev;
            } catch (Horde_Vcs_Exception $e) {}
        }

        pclose($this->_logpipe);
    }

    /**
     * Parses a single log entry from a svn log pipe.
     *
     * @param boolean $parse_files  Wether the log entries contain file
     *                              listings (-v flag).
     *
     * @return array  A list of revision, author, message, date, size and files.
     * @throws Horde_Vcs_Exception
     */
    public function parseLog($parse_files = true)
    {
        $line = fgets($this->_logpipe);
        if (feof($this->_logpipe) || !$line) {
            throw new Horde_Vcs_Exception('No more data');
        }

        if (preg_match('/^r([0-9]*) \| (.*?) \| (.*) \(.*\) \| ([0-9]*) lines?$/', $line, $matches)) {
            $rev = $matches[1];
            $author = $matches[2];
            $date = strtotime($matches[3]);
            $size = $matches[4];
        } else {
            throw new Horde_Vcs_Exception('Unknown log format: ' . $line);
        }

        fgets($this->_logpipe);

        $files = array();
        if ($parse_files) {
            while (($line = trim(fgets($this->_logpipe))) != '') {
                list($mode, $file) = explode(' ', trim($line));
                $files[ltrim($file, '/')] = array('status' => $mode);
            }
        }

        $log = '';
        for ($i = 0; $i != $size; ++$i) {
            $log .= rtrim(fgets($this->_logpipe)) . "\n";
        }
        $log = rtrim($log);

        fgets($this->_logpipe);

        return array($rev, $author, $log, $date, $size, $files);
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

    /**
     * Returns a log object for the most recent log entry of this file.
     *
     * @return Horde_Vcs_QuickLog_Svn  Log object of the last entry in the file.
     * @throws Horde_Vcs_Exception
     */
    public function getLastLog()
    {
        $cmd = $this->_rep->getCommand() . ' log -l 1 ' . escapeshellarg($this->getPath()) . ' 2>&1';
        $this->_logpipe = popen($cmd, 'r');
        if (!$this->_logpipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn log: ' . $cmd);
        }

        $header = fgets($this->_logpipe);
        if (!strspn($header, '-')) {
            throw new Horde_Vcs_Exception('Error executing svn log: ' . $header);
        }

        list($rev, $author, $log, $date) = $this->parseLog(false);
        pclose($this->_logpipe);

        return new Horde_Vcs_QuickLog_Svn($this->_rep, $rev, $date, $author, $log);
    }
}
