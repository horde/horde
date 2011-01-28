<?php
/**
 * Horde_Vcs_Git file class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_Git extends Horde_Vcs_File
{
    /**
     * The master list of revisions for this file.
     *
     * @var array
     */
    protected $_revlist = array();

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
        $log_list = null;

        if (empty($this->_branch)) { $this->_branch = $this->_rep->getDefaultBranch(); }

        /* First, grab the master list of revisions. If quicklog is specified,
         * we don't need this master list - we are only concerned about the
         * most recent revision for the given branch. */
        if ($this->_quicklog) {
            $branchlist = array($this->_branch);
        } else {
            if (version_compare($this->_rep->version, '1.6.0', '>=')) {
                $cmd = $this->_rep->getCommand() . ' rev-list --branches -- ' . escapeshellarg($this->queryModulePath()) . ' 2>&1';
            } else {
                $cmd = $this->_rep->getCommand() . ' branch -v --no-abbrev';
                exec($cmd, $branch_heads);
                if (stripos($branch_heads[0], 'fatal') === 0) {
                    throw new Horde_Vcs_Exception(implode(', ', $branch_heads));
                }
                foreach ($branch_heads as &$hd) {
                    $line = explode(' ', substr($hd, 2));
                    $hd = $line[1];
                }

                $cmd = $this->_rep->getCommand() . ' rev-list ' . implode(' ', $branch_heads) . ' -- ' . escapeshellarg($this->queryModulePath()) . ' 2>&1';
            }

            exec($cmd, $revs);
            if (count($revs) == 0) {
                if (!$this->_rep->isFile($this->queryModulePath(), isset($opts['branch']) ? $opts['branch'] : null)) {
                    throw new Horde_Vcs_Exception('No such file: ' . $this->queryModulePath());
                } else {
                    throw new Horde_Vcs_Exception('No revisions found');
                }
            }

            if (stripos($revs[0], 'fatal') === 0) {
                throw new Horde_Vcs_Exception(implode(', ', $revs));
            }

            $this->_revs = $revs;

            $branchlist = array_keys($this->queryBranches());
        }

        $revs = array();
        $cmd = $this->_rep->getCommand() . ' rev-list' . ($this->_quicklog ? ' -n 1' : '') . ' ' . escapeshellarg($this->_branch) . ' -- ' . escapeshellarg($this->queryModulePath()) . ' 2>&1';
        exec($cmd, $revs);

        if (!empty($revs)) {
            if (stripos($revs[0], 'fatal') === 0) {
                throw new Horde_Vcs_Exception(implode(', ', $revs));
            }

            $this->_revlist[$this->_branch] = $revs;

            $log_list = $revs;

            if ($this->_quicklog) {
                $this->_revs[] = reset($revs);
            }
        }

        if (is_null($log_list)) {
            $log_list = ($this->_quicklog || empty($this->_branch))
                ? $this->_revs
                : array();
        }

        foreach ($log_list as $val) {
            $this->logs[$val] = $this->_rep->getLogObject($this, $val);
        }
    }

    /**
     * Get the hash name for this file at a specific revision.
     *
     * @param string $rev  Revision string.
     *
     * @return string  Commit hash.
     */
    public function getHashForRevision($rev)
    {
        $this->_ensureLogsInitialized();
        if (!isset($this->logs[$rev])) {
            throw new Horde_Vcs_Exception('This file doesn\'t exist at that revision');
        }
        return $this->logs[$rev]->getHashForPath($this->queryModulePath());
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return ($this->_dir == '.')
            ? $this->_name
            : parent::queryModulePath();
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
        return $this->_revlist;
    }

    /**
     * TODO
     */
    public function queryBranch($rev)
    {
        $branches = array();

        foreach (array_keys($this->_revlist) as $val) {
            if (array_search($rev, $this->_revlist[$val]) !== false) {
                $branches[] = $val;
            }
        }

        return $branches;
    }

    /**
     * Return the "base" filename (i.e. the filename needed by the various
     * command line utilities).
     *
     * @return string  A filename.
     */
    public function queryPath()
    {
        return $this->queryModulePath();
    }

    /**
     * TODO
     */
    public function queryBranches()
    {
        /* If dealing with a branch that is not explicitly named (i.e. an
         * implicit branch for a given tree-ish commit ID), we need to add
         * that information to the branch list. */
        $revlist = $this->_rep->getBranchList();
        if (!empty($this->_branch) &&
            !in_array($this->_branch, $revlist)) {
            $revlist[$this->_branch] = $this->_branch;
        }
        return $revlist;
    }

    /**
     * TODO
     */
    public function queryLogs($rev = null)
    {
        if (is_null($rev)) {
            $this->_ensureLogsInitialized();
            return $this->logs;
        } else {
            if (!isset($this->logs[$rev])) {
                $this->logs[$rev] = $this->_rep->getLogObject($this, $rev);
            }

            return isset($this->logs[$rev]) ? $this->logs[$rev] : null;
        }
    }

    /**
     * Return the last Horde_Vcs_Log object in the file.
     *
     * @return Horde_Vcs_Log  Log object of the last entry in the file.
     * @throws Horde_Vcs_Exception
     */
    public function queryLastLog()
    {
        if (empty($this->_branch)) {
            return parent::queryLastLog();
        }

        $this->_ensureLogsInitialized();

        $rev = reset($this->_revlist[$this->_branch]);
        if (!is_null($rev)) {
            if (isset($this->logs[$rev])) {
                return $this->logs[$rev];
            }
        }

        throw new Horde_Vcs_Exception('No revisions');
    }
}