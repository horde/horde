<?php
/**
 * Horde_Vcs_Git implementation.
 *
 * Constructor args:
 * <pre>
 * 'sourceroot': The source root for this repository
 * 'paths': Hash with the locations of all necessary binaries: 'git'
 * </pre>
 *
 * @TODO find bad output earlier - use proc_open, check stderr or result codes?
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
class Horde_Vcs_Git extends Horde_Vcs
{
    /**
     * Does driver support patchsets?
     *
     * @var boolean
     */
    protected $_patchsets = true;

    /**
     * Does driver support branches?
     *
     * @var boolean
     */
    protected $_branches = true;

    /**
     * Does driver support snapshots?
     *
     * @var boolean
     */
    protected $_snapshots = true;

    /**
     * The available diff types.
     *
     * @var array
     */
    protected $_diffTypes = array('unified');

    /**
     * The list of branches for the repo.
     *
     * @var array
     */
    protected $_branchlist;

    /**
     * The git version
     *
     * @var string
     */
    public $version;

    /**
     * @throws Horde_Vcs_Exception
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!is_executable($this->getPath('git'))) {
            throw new Horde_Vcs_Exception('Missing git binary (' . $this->getPath('git') . ' is missing or not executable)');
        }

        $v = trim(shell_exec($this->getPath('git') . ' --version'));
        $this->version = preg_replace('/[^\d\.]/', '', $v);

        // Try to find the repository if we don't have the exact path. @TODO put
        // this into a builder method/object and cache the results.
        if (!file_exists($this->sourceroot() . '/HEAD')) {
            if (file_exists($this->sourceroot() . '.git/HEAD')) {
                $this->_sourceroot .= '.git';
            } elseif (file_exists($this->sourceroot() . '/.git/HEAD')) {
                $this->_sourceroot .= '/.git';
            } else {
                throw new Horde_Vcs_Exception('Can not find git repository.');
            }
        }
    }

    /**
     * TODO
     */
    public function isValidRevision($rev)
    {
        return $rev && preg_match('/^[a-f0-9]+$/i', $rev);
    }

    /**
     * TODO
     */
    public function isFile($where, $branch = null)
    {
        if (!$branch) {
            $branch = $this->getDefaultBranch();
        }

        $where = str_replace($this->sourceroot() . '/', '', $where);
        $command = $this->getCommand() . ' ls-tree ' . escapeshellarg($branch) . ' ' . escapeshellarg($where) . ' 2>&1';
        exec($command, $entry, $retval);

        if (!count($entry)) { return false; }

        $data = explode(' ', $entry[0]);
        return ($data[1] == 'blob');
    }

    /**
     * TODO
     */
    public function getCommand()
    {
        return escapeshellcmd($this->getPath('git')) . ' --git-dir=' . escapeshellarg($this->sourceroot());
    }

    /**
     * TODO
     *
     * @throws Horde_Vcs_Exception
     */
    public function annotate($fileob, $rev)
    {
        $this->assertValidRevision($rev);

        $command = $this->getCommand() . ' blame -p ' . escapeshellarg($rev) . ' -- ' . escapeshellarg($fileob->queryModulePath()) . ' 2>&1';
        $pipe = popen($command, 'r');
        if (!$pipe) {
            throw new Horde_Vcs_Exception('Failed to execute git annotate: ' . $command);
        }

        $curr_rev = null;
        $db = $lines = array();
        $lines_group = $line_num = 0;

        while (!feof($pipe)) {
            $line = rtrim(fgets($pipe, 4096));

            if (!$line || ($line[0] == "\t")) {
                if ($lines_group) {
                    $lines[] = array(
                        'author' => $db[$curr_rev]['author'] . ' ' . $db[$curr_rev]['author-mail'],
                        'date' => $db[$curr_rev]['author-time'],
                        'line' => $line ? substr($line, 1) : '',
                        'lineno' => $line_num++,
                        'rev' => $curr_rev
                    );
                    --$lines_group;
                }
            } elseif ($line != 'boundary') {
                if ($lines_group) {
                    list($prefix, $linedata) = explode(' ', $line, 2);
                    switch ($prefix) {
                    case 'author':
                    case 'author-mail':
                    case 'author-time':
                    //case 'author-tz':
                        $db[$curr_rev][$prefix] = trim($linedata);
                        break;
                    }
                } else {
                    $curr_line = explode(' ', $line);
                    $curr_rev = $curr_line[0];
                    $line_num = $curr_line[2];
                    $lines_group = isset($curr_line[3]) ? $curr_line[3] : 1;
                }
            }
        }

        pclose($pipe);

        return $lines;
    }

    /**
     * Function which returns a file pointing to the head of the requested
     * revision of a file.
     *
     * @param string $fullname  Fully qualified pathname of the desired file
     *                          to checkout
     * @param string $rev       Revision number to check out
     *
     * @return resource  A stream pointer to the head of the checkout.
     * @throws Horde_Vcs_Exception
     */
    public function checkout($file, $rev)
    {
        $this->assertValidRevision($rev);

        $file_ob = $this->getFileObject($file);
        $hash = $file_ob->getHashForRevision($rev);
        if ($hash == '0000000000000000000000000000000000000000') {
            throw new Horde_Vcs_Exception($file . ' is deleted in commit ' . $rev);
        }

        if ($pipe = popen($this->getCommand() . ' cat-file blob ' . $hash . ' 2>&1', VC_WINDOWS ? 'rb' : 'r')) {
            return $pipe;
        }

        throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
    }

    /**
     * Create a range of revisions between two revision numbers.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $r1            The initial revision.
     * @param string $r2            The ending revision.
     *
     * @return array  The revision range, or empty if there is no straight
     *                line path between the revisions.
     */
    public function getRevisionRange($file, $r1, $r2)
    {
        $revs = $this->_getRevisionRange($file, $r1, $r2);
        return empty($revs)
            ? array_reverse($this->_getRevisionRange($file, $r2, $r1))
            : $revs;
    }

    /**
     * TODO
     */
    protected function _getRevisionRange($file, $r1, $r2)
    {
        $cmd = $this->getCommand() . ' rev-list ' . escapeshellarg($r1 . '..' . $r2) . ' -- ' . escapeshellarg($file->queryModulePath());
        $revs = array();

        exec($cmd, $revs);
        return array_map('trim', $revs);
    }

    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $rev1          Original revision number to compare from.
     * @param string $rev2          New revision number to compare against.
     * @param array $opts           The following optional options:
     * <pre>
     * 'num' - (integer) DEFAULT: 3
     * 'type' - (string) DEFAULT: 'unified'
     * 'ws' - (boolean) DEFAULT: true
     * </pre>
     *
     * @return string  The diff text.
     */
    protected function _diff($file, $rev1, $rev2, $opts)
    {
        $diff = array();
        $flags = '';

        if (!$opts['ws']) {
            $flags .= ' -b -w ';
        }

        if (!$rev1) {
            $command = $this->getCommand() . ' show --oneline ' . escapeshellarg($rev2) . ' -- ' . escapeshellarg($file->queryModulePath()) . ' 2>&1';
        } else {
            switch ($opts['type']) {
            case 'unified':
                $flags .= '--unified=' . escapeshellarg((int)$opts['num']);
                break;
            }

            // @TODO: add options for $hr options - however these may not
            // be compatible with some diffs.
            $command = $this->getCommand() . ' diff -M -C ' . $flags . ' --no-color ' . escapeshellarg($rev1 . '..' . $rev2) . ' -- ' . escapeshellarg($file->queryModulePath()) . ' 2>&1';
        }

        exec($command, $diff, $retval);
        return $diff;
    }

    /**
     * Returns an abbreviated form of the revision, for display.
     *
     * @param string $rev  The revision string.
     *
     * @return string  The abbreviated string.
     */
    public function abbrev($rev)
    {
        return substr($rev, 0, 7) . '[...]';
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
        if (!isset($this->_branchlist)) {
            $this->_branchlist = array();
            exec($this->getCommand() . ' show-ref --heads', $branch_list);

            foreach ($branch_list as $val) {
                $line = explode(' ', trim($val), 2);
                $this->_branchlist[substr($line[1], strrpos($line[1], '/') + 1)] = $line[0];
            }
        }

        return $this->_branchlist;
    }

    /**
     * @TODO ?
     */
    public function getDefaultBranch()
    {
        return 'master';
    }
}
