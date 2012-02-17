<?php
/**
 * RCS log class.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Log_Rcs extends Horde_Vcs_Log_Base
{
    /**
     * This method parses branches even though RCS doesn't support
     * branches. But rlog from the RCS tools supports them, and displays them
     * even on RCS repositories.
     */
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
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+\+(\d+)\s\-(\d+))?|", $line, $parts)) {
                    $this->_date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $this->_author = $parts[7];
                    $this->_state = $parts[8];
                    if (isset($parts[9])) {
                        $this->_lines = '+' . $parts[10] . ' -' . $parts[11];
                        $this->_files[$this->_file->getSourcerootPath()] = array(
                            'added' => $parts[10],
                            'deleted' => $parts[11]
                        );
                    }
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
        $this->_tags = $this->_file->getRevisionSymbol($this->_rev);

        $this->_setSymbolicBranches();

        $branches = $this->_file->getBranches();
        $key = array_keys($branches, $this->_rev);
        $this->_branch = empty($key)
            ? array_keys($branches, $this->_rep->strip($this->_rev, 1))
            : $key;
    }

    /**
     * TODO
     *
     * Ignoring branches in this driver.
     */
    public function setBranch($branch)
    {
    }
}
