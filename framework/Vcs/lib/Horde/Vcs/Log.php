<?php
/**
 * Horde_Vcs_Log class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Log
{
    protected $_rep;
    protected $_file;
    protected $_files = array();
    protected $_rev;
    protected $_author;
    protected $_tags = array();
    protected $_date;
    protected $_log;
    protected $_state;
    protected $_lines = '';
    protected $_branch;
    protected $_branches = array();
    protected $_symbolicBranches = array();

    /**
     * @var boolean
     */
    protected $_initialized;

    /**
     * Constructor.
     */
    public function __construct($rev)
    {
        $this->_rev = $rev;
    }

    protected function _ensureInitialized()
    {
        if (!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    /**
     * When serializing, don't return the repository object
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('_file', '_rep'));
    }

    /**
     * TODO
     */
    public function setRepository($rep)
    {
        $this->_rep = $rep;
    }

    public function setFile(Horde_Vcs_File $file)
    {
        $this->_file = $file;
    }

    /**
     * TODO
     */
    public function queryRevision()
    {
        $this->_ensureInitialized();
        return $this->_rev;
    }

    /**
     * TODO
     */
    public function queryDate()
    {
        $this->_ensureInitialized();
        return $this->_date;
    }

    /**
     * TODO
     */
    public function queryAuthor()
    {
        $this->_ensureInitialized();
        return $this->_author;
    }

    /**
     * TODO
     */
    public function queryLog()
    {
        $this->_ensureInitialized();
        return $this->_log;
    }

    /**
     * TODO
     */
    public function queryBranch()
    {
        $this->_ensureInitialized();
        return $this->_branch;
    }

    /**
     * TODO
     */
    public function queryChangedLines()
    {
        $this->_ensureInitialized();
        return $this->_lines;
    }

    /**
     * TODO
     */
    public function queryTags()
    {
        $this->_ensureInitialized();
        return $this->_tags;
    }

    /**
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return array  Hash of symbolic names => branch numbers.
     */
    public function querySymbolicBranches()
    {
        $this->_ensureInitialized();
        return $this->_symbolicBranches;
    }

    protected function _setSymbolicBranches()
    {
        $this->_symbolicBranches = array();
        $branches = $this->_file->queryBranches();

        foreach ($this->_branches as $branch) {
            if (($key = array_search($branch, $branches)) !== false) {
                $this->_symbolicBranches[$key] = $branch;
            }
        }
    }

    /**
     * TODO
     */
    public function queryFiles($file = null)
    {
        $this->_ensureInitialized();
        return is_null($file)
            ? $this->_files
            : (isset($this->_files[$file]) ? $this->_files[$file] : array());
    }

    public function getAddedLines()
    {
        $this->_ensureInitialized();
        $lines = 0;
        foreach ($this->_files as $file) {
            if (isset($file['added'])) {
                $lines += $file['added'];
            }
        }
        return $lines;
    }

    public function getDeletedLines()
    {
        $this->_ensureInitialized();
        $lines = 0;
        foreach ($this->_files as $file) {
            if (isset($file['deleted'])) {
                $lines += $file['deleted'];
            }
        }
        return $lines;
    }
}
