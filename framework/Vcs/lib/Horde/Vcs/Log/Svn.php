<?php
/**
 * Horde_Vcs_Log_Svn class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Vcs
 */
class Horde_Vcs_Log_Svn extends Horde_Vcs_Log
{
    /**
     * TODO
     */
    protected $_files = array();

    /**
     * Constructor.
     */
    protected function _init()
    {
        $line = fgets($this->_file->logpipe);
        if (feof($this->_file->logpipe) || !$line) {
            throw new Horde_Vcs_Exception('No more data');
        }

        if (preg_match('/^r([0-9]*) \| (.*?) \| (.*) \(.*\) \| ([0-9]*) lines?$/', $line, $matches)) {
            $this->_rev = $matches[1];
            $this->_author = $matches[2];
            $this->_date = strtotime($matches[3]);
            $size = $matches[4];
        } else {
            throw new Horde_Vcs_Exception('SVN Error');
        }

        fgets($this->_file->logpipe);

        while (($line = trim(fgets($this->_file->logpipe))) != '') {
            $this->_files[] = $line;
        }

        for ($i = 0; $i != $size; ++$i) {
            $this->_log = $this->_log . chop(fgets($this->_file->logpipe)) . "\n";
        }

        $this->_log = chop($this->_log);
        fgets($this->_file->logpipe);
    }

    /**
     * TODO
     */
    public function queryFiles()
    {
        return $this->_files;
    }
}
