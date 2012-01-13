<?php
/**
 * Subversion log class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Vcs
 */
class Horde_Vcs_Log_Svn extends Horde_Vcs_Log_Base
{
    /**
     * TODO
     */
    protected $_files = array();

    /**
     */
    protected function _init()
    {
        list($this->_rev,
             $this->_author,
             $this->_log,
             $this->_date,
             $this->_size,
             $this->_files) = $this->_file->parseLog();
    }

    /**
     * TODO
     */
    public function getFiles($file = null)
    {
        return $this->_files;
    }
}
