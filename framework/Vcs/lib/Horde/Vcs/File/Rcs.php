<?php
/**
 * RCS file class.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_File_Rcs extends Horde_Vcs_File_Base
{
    /**
     * The current driver.
     *
     * @var string
     */
    protected $_driver = 'Rcs';

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

    /**
     * This method parses branches even though RCS doesn't support
     * branches. But rlog from the RCS tools supports them, and displays them
     * even on RCS repositories.
     */
    protected function _init()
    {
        /* Check that we are actually in the filesystem. */
        $file = $this->getFullPath();
        if (!is_file($file)) {
            throw new Horde_Vcs_Exception('File Not Found: ' . $file);
        }

        $ret_array = array();
        $cmd = escapeshellcmd($this->_rep->getPath('rlog'))
            . ' ' . escapeshellarg($file);
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
                    $log = $this->_getLog();
                    $rev = $log->getRevision();
                    $onbranch = false;
                    $onhead = substr_count($rev, '.') == 1;

                    // Determine branch information.
                    if ($onhead) {
                        $onbranch = (empty($this->_branch) || $this->_branch == 'HEAD') ||
                            ($this->_rep->cmp($branches[$this->_branch], $rev) === 1);
                        $log->setBranch('HEAD');
                    } else {
                        foreach ($branches as $key => $val) {
                            if (strpos($rev, $val) === 0) {
                                $onbranch = true;
                                $log->setBranch($key);
                                if (!isset($this->_branches[$key])) {
                                    $this->_branches[$key] = $rev;
                                    $this->_revlist[$key] = $this->_rep->getRevisionRange($this, '1.1', $rev);
                                }
                                if ($this->_branch == 'HEAD') {
                                    break 2;
                                }
                                break;
                            }
                        }
                    }

                    if ($onbranch) {
                        $this->_revs[] = $rev;
                        $this->_logs[$rev] = $log;
                    }

                    $this->_accum = array();
                }
                break;
            }
        }
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return string  Filename without repository extension
     */
    public function getFileName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return string  Fully qualified filename of this object.
     */
    public function getFullPath()
    {
        $path = $this->_rep->sourceroot;
        if (strlen($this->_dir)) {
            $path .= '/' . $this->_dir;
        }
        $path .= '/' . $this->_name;
        return $path;
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function getSourcerootPath()
    {
        return substr(parent::getSourcerootPath(), 0, -2);
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
        /* Revisions in RCS/CVS logs are not ordered by date, so use the logic
         * from the base object. */
        return $this->_rep->prev($rev);
    }

    /**
     * Returns a log object for the most recent log entry of this file.
     *
     * @return Horde_Vcs_QuickLog_Rcs  Log object of the last entry in the file.
     * @throws Horde_Vcs_Exception
     */
    public function getLastLog()
    {
        /* Check that we are actually in the filesystem. */
        $file = $this->getFullPath();
        if (!is_file($file)) {
            throw new Horde_Vcs_Exception('File Not Found: ' . $file);
        }

        $cmd = escapeshellcmd($this->_rep->getPath('rlog')) . ' -r';
        if (!empty($this->_branch)) {
            $branches = $this->getBranches();
            $branch = $branches[$this->_branch];
            $cmd .= substr($branch, 0, strrpos($branch, '.')) . '.';
        }
        $cmd .= ' ' . escapeshellarg($file);
        exec($cmd, $ret_array, $retval);

        if ($retval) {
            throw new Horde_Vcs_Exception('Failed to spawn rlog to retrieve file log information for ' . $file);
        }

        $state = 'init';
        $log = '';
        foreach ($ret_array as $line) {
            switch ($state) {
            case 'init':
                if (strpos($line, '----------') === 0) {
                    $state = 'revision';
                }
                break;

            case 'revision':
                if (preg_match("/revision (.+)$/", $line, $parts)) {
                    $rev = $parts[1];
                    $state = 'details';
                }
                break;

            case 'details':
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+\+(\d+)\s\-(\d+))?|", $line, $parts)) {
                    $date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $author = $parts[7];
                    $state = 'log';
                }
                break;

            case 'log':
                if (strpos($line, '==============================') === 0) {
                    $log = substr($log, 0, -1);
                    break 2;
                }
                $log .= $line . "\n";
            }
        }

        $class = 'Horde_Vcs_QuickLog_' . $this->_driver;
        return new $class($this->_rep, $rev, $date, $author, $log);
    }

    /**
     * TODO
     */
    public function getRevisionSymbol($rev)
    {
        return isset($this->_revsym[$rev])
            ? $this->_revsym[$rev]
            : array();
    }

    /**
     * TODO
     */
    public function getAccum()
    {
        return $this->_accum;
    }
}
