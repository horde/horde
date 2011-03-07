<?php
/**
 * Horde_Vcs_Cvs file class.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_File_Cvs extends Horde_Vcs_File
{
    /**
     * TODO
     *
     * @var string
     */
    protected $_accum;

    /**
     * TODO
     *
     * @var array
     */
    protected $_revsym = array();

    /**
     * TODO
     *
     * @var array
     */
    protected $_symrev = array();

    /**
     * TODO
     *
     * @var array
     */
    protected $_revlist = array();

    /**
     * TODO
     *
     * @var array
     */
    protected $_branches = array();

    private $_initialized;

    protected function _init()
    {
        /* Check that we are actually in the filesystem. */
        $file = $this->_dir . '/' . $this->_name;
        if (!is_file($file)) {
            throw new Horde_Vcs_Exception('File Not Found: ' . $file);
        }

        $ret_array = array();
        $cmd = escapeshellcmd($this->_rep->getPath('rlog')) . ($this->_quicklog ? ' -r' : '') . ' ' . escapeshellarg($file);
        exec($cmd, $ret_array, $retval);

        if ($retval) {
            throw new Horde_Vcs_Exception('Failed to spawn rlog to retrieve file log information for ' . $file);
        }

        $branches = array();
        $state = 'init';

        foreach ($ret_array as $line) {
            switch ($state) {
            case 'init':
                if (strpos($line, 'head: ') === 0) {
                    $this->_branches['HEAD'] = substr($line, 6);
                    $this->_revlist['HEAD'] = $this->_rep->getRevisionRange($this, '1.1', $this->_branches['HEAD']);
                } elseif (strpos($line, 'branch:') === 0) {
                    $state = 'rev';
                }
                break;

            case 'rev':
                if (strpos($line, '----------') === 0) {
                    $state = 'info';
                } elseif (preg_match("/^\s+([^:]+):\s+([\d\.]+)/", $line, $regs)) {
                    // Check to see if this is a branch.
                    if (preg_match('/^(\d+(\.\d+)+)\.0\.(\d+)$/', $regs[2])) {
                        $rev = $regs[2];
                        $end = strrpos($rev, '.');
                        $rev[$end] = 0;
                        $branchRev = (($end2 = strrpos($rev, '.')) === false)
                            ? substr($rev, ++$end)
                            : substr_replace($rev, '.', $end2, ($end - $end2 + 1));

                        /* $branchRev is only the branching point, NOT the
                         * HEAD of the branch. To determine the HEAD, we need
                         * to parse all of the log data first. Yuck. */
                        $branches[$regs[1]] = $branchRev . '.';
                    } else {
                        $this->_symrev[$regs[1]] = $regs[2];
                        if (empty($this->_revsym[$regs[2]])) {
                            $this->_revsym[$regs[2]] = array();
                        }
                        $this->_revsym[$regs[2]][] = $regs[1];
                    }
                }
                break;

            case 'info':
                if ((strpos($line, '==============================') === false) &&
                    (strpos($line, '----------------------------') === false)) {
                    $this->_accum[] = $line;
                } elseif (count($this->_accum)) {
                    $log = $this->_rep->getLogObject($this, null);
                    $rev = $log->queryRevision();
                    $onbranch = false;
                    $onhead = (substr_count($rev, '.') == 1);

                    // Determine branch information.
                    if ($onhead) {
                        $onbranch = (empty($this->_branch) || $this->_branch == 'HEAD') ||
                            ($this->_rep->cmp($branches[$this->_branch], $rev) === 1);
                    } elseif ($this->_branch != 'HEAD') {
                        foreach ($branches as $key => $val) {
                            if (strpos($rev, $val) === 0) {
                                $onbranch = true;
                                $log->setBranch($key);
                                if (!isset($this->_branches[$key])) {
                                    $this->_branches[$key] = $rev;
                                    $this->_revlist[$key] = $this->_rep->getRevisionRange($this, '1.1', $rev);
                                }
                                break;
                            }
                        }
                    }

                    if ($onbranch) {
                        $this->_revs[] = $rev;
                        $this->logs[$rev] = $log;
                    }

                    $this->_accum = array();
                }
                break;
            }
        }
    }

    protected function _ensureRevisionsInitialized()
    {
        if(!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    protected function _ensureLogsInitialized()
    {
        if(!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    /**
     * If this file is present in an Attic directory, this indicates it.
     *
     * @return boolean  True if file is in the Attic, and false otherwise
     */
    public function isDeleted()
    {
        return (substr($this->_dir, -5) == 'Attic');
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return string  Filename without repository extension
     */
    public function queryName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return Fully qualified filename of this object
     */
    public function queryFullPath()
    {
        return parent::queryModulePath();
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return preg_replace('|^'. $this->_rep->sourceroot() . '/?(.*),v$|', '\1', $this->queryFullPath());
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
        return $this->_revlist();
    }

    /**
     * TODO
     */
    public function queryRevsym($rev)
    {
        return isset($this->_revsym[$rev])
            ? $this->_revsym[$rev]
            : array();
    }

    /**
     * TODO
     */
    public function querySymbolicRevisions()
    {
        return $this->_symrev;
    }

    /**
     * TODO
     */
    public function getAccum()
    {
        return $this->_accum;
    }

    /**
     * TODO
     */
     public function queryBranches()
     {
         return $this->_branches;
     }

}