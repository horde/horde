<?php
/**
 * Git file class.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_File_Git extends Horde_Vcs_File_Base
{
    /**
     * The master list of revisions for this file.
     *
     * @var array
     */
    protected $_revlist = array();

    protected function _init()
    {
        $log_list = null;

        /* First, grab the master list of revisions. If quicklog is specified,
         * we don't need this master list - we are only concerned about the
         * most recent revision for the given branch. */
        if ($this->_quicklog) {
            if (empty($this->_branch)) {
                $this->_branch = $this->_rep->getDefaultBranch();
            }
            $branchlist = array($this->_branch);
        } else {
            if (version_compare($this->_rep->version, '1.6.0', '>=')) {
                $cmd = 'rev-list --branches -- '
                    . escapeshellarg($this->getSourcerootPath());
            } else {
                list($stream, $result) = $this->_rep->runCommand(
                    'branch -v --no-abbrev');
                $branch_heads = array();
                while (!feof($result)) {
                    $line = explode(' ', substr(rtrim(fgets($result)), 2));
                    $branch_heads[] = $line[1];
                }
                fclose($result);
                proc_close($stream);

                $cmd = 'rev-list ' . implode(' ', $branch_heads) . ' -- '
                    . escapeshellarg($this->getSourcerootPath());
            }

            list($stream, $result) = $this->_rep->runCommand($cmd);
            if (feof($result)) {
                $branch = empty($this->_branch) ? null : $this->_branch;
                if (!$this->_rep->isFile($this->getSourcerootPath(), $branch)) {
                    throw new Horde_Vcs_Exception('No such file: ' . $this->getSourcerootPath());
                } else {
                    throw new Horde_Vcs_Exception('No revisions found');
                }
            }

            while (!feof($result)) {
                $line = trim(fgets($result));
                if (strlen($line)) {
                    $this->_revs[] = $line;
                }
            }
            fclose($result);
            proc_close($stream);

            $branchlist = empty($this->_branch)
                ? array_keys($this->getBranches())
                : array($this->_branch);
        }

        $revs = array();
        $cmd = 'rev-list';
        if ($this->_quicklog) {
            $cmd .= ' -n 1';
        }
        foreach ($branchlist as $branch) {
            $cmd .= ' ' . escapeshellarg($branch);
        }
        $cmd .= ' -- ' . escapeshellarg($this->getSourcerootPath());
        list($stream, $result) = $this->_rep->runCommand($cmd);

        if (!feof($result)) {
            $revs = explode("\n", trim(stream_get_contents($result)));
            if (!empty($this->_branch)) {
                $this->_revlist[$this->_branch] = $revs;
            }
            $log_list = $revs;
            if ($this->_quicklog) {
                $this->_revs[] = reset($revs);
            }
        }
        fclose($result);
        proc_close($stream);

        if (is_null($log_list)) {
            $log_list = ($this->_quicklog || empty($this->_branch))
                ? $this->_revs
                : array();
        }

        foreach ($log_list as $val) {
            $this->logs[$val] = $this->_rep->getLog($this, $val);
        }
    }

    /**
     * Returns the last revision of the current file on the HEAD branch.
     *
     * @return string  Last revision of the current file.
     * @throws Horde_Vcs_Exception
     */
    public function getRevision()
    {
        $this->_ensureRevisionsInitialized();
        if (empty($this->_branch)) {
            return parent::getRevision();
        }

        $rev = reset($this->_revlist[$this->_branch]);
        if (is_null($rev)) {
            throw new Horde_Vcs_Exception('No revisions');
        }

        return $rev;
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
        $this->_ensureRevisionsInitialized();

        if (empty($this->_branch)) {
            return parent::getPreviousRevision($rev);
        }

        $key = array_search($rev, $this->_revlist[$this->_branch]);
        return ($key !== false &&
                isset($this->_revlist[$this->_branch][$key + 1]))
            ? $this->_revlist[$this->_branch][$key + 1]
            : null;
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
        return $this->logs[$rev]->getHashForPath($this->getSourcerootPath());
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
        return $this->_revlist;
    }

    /**
     * Returns all branches that contain a certain revision.
     *
     * @param string $rev  A revision.
     *
     * @return array  A list of branches with this revision.
     */
    public function getBranch($rev)
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
    public function getPath()
    {
        return $this->getSourcerootPath();
    }

    /**
     * TODO
     */
    public function getBranches()
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
    public function getLogs($rev = null)
    {
        if (is_null($rev)) {
            $this->_ensureLogsInitialized();
            return $this->logs;
        } else {
            if (!isset($this->logs[$rev])) {
                $this->logs[$rev] = $this->_rep->getLog($this, $rev);
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
    public function getLastLog()
    {
        if (empty($this->_branch)) {
            return parent::getLastLog();
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

    /**
     * TODO
     */
    public function revisionCount()
    {
        if (empty($this->_branch)) {
            return parent::revisionCount();
        }
        $this->_ensureRevisionsInitialized();
        return count($this->_revlist[$this->_branch]);
    }

    /**
     * TODO
     */
    public function getSymbolicRevisions()
    {
        list($stream, $result) = $this->_rep->runCommand('show-ref --tags');
        $tags = array();
        while (!feof($result)) {
            $line = trim(fgets($result));
            if ($line) {
                list($rev, $tag) = explode(' ', $line);
                $tags[basename($tag)] = $rev;
            }
        }
        fclose($result);
        proc_close($stream);
        return $tags;
    }
}
