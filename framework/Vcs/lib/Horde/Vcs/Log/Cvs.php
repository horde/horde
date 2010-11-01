<?php
/**
 * Horde_Vcs_cvs Log class.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Cvs extends Horde_Vcs_Log
{
    /**
     * Cached branch info.
     *
     * @var string
     */
    protected $_branch;

    private $_initialized;

    protected function _init()
    {
       $raw = $this->_file->getAccum();

        /* Initialise a simple state machine to parse the output of rlog */
        $state = 'init';
        while (!empty($raw) && $state != 'done') {
            switch ($state) {
            /* Found filename, now looking for the revision number */
            case 'init':
                $line = array_shift($raw);
                if (preg_match("/revision (.+)$/", $line, $parts)) {
                    $this->_rev = $parts[1];
                    $state = 'date';
                }
                break;

            /* Found revision and filename, now looking for date */
            case 'date':
                $line = array_shift($raw);
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+([0-9\s+-]+))?|", $line, $parts)) {
                    $this->_date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $this->_author = $parts[7];
                    $this->_state = $parts[8];
                    $this->_lines = isset($parts[10]) ? $parts[10] : '';
                    $state = 'branches';
                }
                break;

            /* Look for a branch point here - format is 'branches:
             * x.y.z; a.b.c;' */
            case 'branches':
                /* If we find a branch tag, process and pop it,
                   otherwise leave input stream untouched */
                if (!empty($raw) &&
                    preg_match("/^branches:\s+(.*)/", $raw[0], $br)) {
                    /* Get the list of branches from the string, and
                     * push valid revisions into the branches array */
                    $brs = preg_split('/;\s*/', $br[1]);
                    foreach ($brs as $brpoint) {
                        if ($this->_rep->isValidRevision($brpoint)) {
                            $this->_branches[] = $brpoint;
                        }
                    }
                    array_shift($raw);
                }

                $state = 'done';
                break;
            }
        }

        /* Assume the rest of the lines are the log message */
        $this->_log = implode("\n", $raw);
        $this->_tags = $this->_file->queryRevsym($this->_rev);
    }

    protected function _ensureInitialized()
    {
        if (!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    /**
     * TODO
     */
    public function setBranch($branch)
    {
        $this->_branch = array($branch);
    }

    /**
     * TODO
     */
    public function queryBranch()
    {
        if (!empty($this->_branch)) {
            return $this->_branch;
        }

        $branches = $this->_file->queryBranches();
        $key = array_keys($branches, $this->_rev);
        return empty($key)
            ? array_keys($branches, $this->_rep->strip($this->_rev, 1))
            : $key;
    }

}