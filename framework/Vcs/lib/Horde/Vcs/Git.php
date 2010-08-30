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
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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

        switch ($opts['type']) {
        case 'unified':
            $flags .= '--unified=' . escapeshellarg((int)$opts['num']);
            break;
        }

        // @TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $this->getCommand() . ' diff -M -C ' . $flags . ' --no-color ' . escapeshellarg($rev1 . '..' . $rev2) . ' -- ' . escapeshellarg($file->queryModulePath()) . ' 2>&1';

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

/**
 * Horde_Vcs_Git directory class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Git extends Horde_Vcs_Directory
{
    /**
     * The current branch.
     *
     * @var string
     */
    protected $_branch;

    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository.
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        $this->_branch = empty($opts['rev'])
            ? $rep->getDefaultBranch()
            : $opts['rev'];

        // @TODO See if we have a valid cache of the tree at this revision

        $dir = $this->queryDir();
        if (substr($dir, 0, 1) == '/') {
            $dir = (string)substr($dir, 1);
        }
        if (strlen($dir) && substr($dir, -1) != '/') {
            $dir .= '/';
        }

        $cmd = $rep->getCommand() . ' ls-tree --full-name ' . escapeshellarg($this->_branch) . ' ' . escapeshellarg($dir) . ' 2>&1';
        $stream = popen($cmd, 'r');
        if (!$stream) {
            throw new Horde_Vcs_Exception('Failed to execute git ls-tree: ' . $cmd);
        }

        // Create two arrays - one of all the files, and the other of
        // all the dirs.
        while (!feof($stream)) {
            $line = fgets($stream);
            if ($line === false) { break; }

            $line = rtrim($line);
            if (!strlen($line))  { continue; }

            list(, $type, , $file) = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            if ($type == 'tree') {
                $this->_dirs[] = basename($file);
            } else {
                $this->_files[] = $rep->getFileObject($file, array('branch' => $this->_branch, 'quicklog' => !empty($opts['quicklog'])));
            }
        }

        pclose($stream);
    }

    /**
     * TODO
     */
    public function getBranches()
    {
        $blist = array_keys($this->_rep->getBranchList());
        if (!in_array($this->_branch, $blist)) {
            $blist[] = $this->_branch;
        }
        return $blist;
    }

}

/**
 * Horde_Vcs_Git file class.
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

        /* First, grab the master list of revisions. If quicklog is specified,
         * we don't need this master list - we are only concerned about the
         * most recent revision for the given branch. */
        if ($this->_quicklog) {
            $branchlist = empty($this->_branch)
                ? array($this->_rep->getDefaultBranch())
                : array($this->_branch);
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

        /* Get the list of revisions. Need to get all revisions, not just
         * those on $this->_branch, for branch determination reasons. */
        foreach ($branchlist as $key) {
            $revs = array();
            $cmd = $this->_rep->getCommand() . ' rev-list ' . ($this->_quicklog ? '-n 1' : '') . ' ' . escapeshellarg($key) . ' -- ' . escapeshellarg($this->queryModulePath()) . ' 2>&1';
            exec($cmd, $revs);

            if (!empty($revs)) {
                if (stripos($revs[0], 'fatal') === 0) {
                    throw new Horde_Vcs_Exception(implode(', ', $revs));
                }

                $this->_revlist[$key] = $revs;

                if (!empty($this->_branch) && ($this->_branch == $key)) {
                    $log_list = $revs;
                }

                if ($this->_quicklog) {
                    $this->_revs[] = reset($revs);
                }
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

        $rev = reset($this->_revlist[$this->_branch]);
        if (!is_null($rev)) {
            if (isset($this->logs[$rev])) {
                return $this->logs[$rev];
            }
        }

        throw new Horde_Vcs_Exception('No revisions');
    }
}

/**
 * Horde_Vcs_Git log class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Git extends Horde_Vcs_Log
{
    /**
     * @var string
     */
    protected $_parent = null;

    /**
     * @var boolean
     */
    private $_initialized;

    protected function _ensureInitialized()
    {
        if (!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    protected function _init()
    {
        /* Get diff statistics. */
        $stats = array();
        $cmd = $this->_rep->getCommand() . ' diff-tree --numstat ' . escapeshellarg($this->_rev);
        exec($cmd, $output);

        reset($output);
        // Skip the first entry (it is the revision number)
        next($output);
        while (list(,$v) = each($output)) {
            $tmp = explode("\t", $v);
            $stats[$tmp[2]] = array_slice($tmp, 0, 2);
        }

        // @TODO use Commit, CommitDate, and Merge properties
        $cmd = $this->_rep->getCommand() . ' whatchanged --no-color --pretty=format:"Rev:%H%nParents:%P%nAuthor:%an <%ae>%nAuthorDate:%at%nRefs:%d%n%n%s%n%b" --no-abbrev -n 1 ' . escapeshellarg($this->_rev);
        $pipe = popen($cmd, 'r');
        if (!is_resource($pipe)) {
            throw new Horde_Vcs_Exception('Unable to run ' . $cmd . ': ' . error_get_last());
        }

        while (true) {
            $line = trim(fgets($pipe));
            if (!strlen($line)) { break; }
            if (strpos($line, ':') === false) {
                throw new Horde_Vcs_Exception('Malformed log line: ' . $line);
            }

            list($key, $value) = explode(':', $line, 2);
            $value = trim($value);

            switch (trim($key)) {
            case 'Rev':
                if ($this->_rev != $value) {
                    fclose($pipe);
                    throw new Horde_Vcs_Exception('Expected ' . $this->_rev . ', got ' . $value);
                }
                break;

            case 'Parents':
                // @TODO: More than 1 parent?
                $this->_parent = $value;
                break;

            case 'Author':
                $this->_author = $value;
                break;

            case 'AuthorDate':
                $this->_date = $value;
                break;

            case 'Refs':
                if ($value) {
                    $value = substr($value, 1, -1);
                    foreach (explode(',', $value) as $val) {
                        $val = trim($val);
                        if (strpos($val, 'refs/tags/') === 0) {
                            $this->_tags[] = substr($val, 10);
                        }
                    }
                    if (!empty($this->_tags)) {
                        sort($this->_tags);
                    }
                }
                break;
            }
        }

        $log = '';
        $line = fgets($pipe);
        while ($line !== false && substr($line, 0, 1) != ':') {
            $log .= $line;
            $line = fgets($pipe);
        }
        $this->_log = trim($log);

        // Build list of files in this revision. The format of these lines is
        // documented in the git diff-tree documentation:
        // http://www.kernel.org/pub/software/scm/git/docs/git-diff-tree.html
        while ($line) {
            preg_match('/:(\d+) (\d+) (\w+) (\w+) (.+)\t(.+)(\t(.+))?/', $line, $matches);

            $statinfo = isset($stats[$matches[6]])
                ? array('added' => $stats[$matches[6]][0], 'deleted' => $stats[$matches[6]][1])
                : array();

            $this->_files[$matches[6]] = array_merge(array(
                'srcMode' => $matches[1],
                'dstMode' => $matches[2],
                'srcSha1' => $matches[3],
                'dstSha1' => $matches[4],
                'status' => $matches[5],
                'srcPath' => $matches[6],
                'dstPath' => isset($matches[7]) ? $matches[7] : ''
            ), $statinfo);

            $line = fgets($pipe);
        }

        fclose($pipe);
    }

    /**
     * TODO
     */
    public function getHashForPath($path)
    {
        return $this->_files[$path]['dstSha1'];
    }

    /**
     * TODO
     */
    public function queryBranch()
    {
        return $this->_file->queryBranch($this->_rev);
    }

    /**
     * TODO
     */
    public function queryParent()
    {
        return $this->_parent;
    }

}

/**
 * Horde_Vcs_Git Patchset class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_Git extends Horde_Vcs_Patchset
{
    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param array $opts     Additional options.
     * <pre>
     * 'file' - (string) The filename to produce patchsets for.
     * 'range' - (array) The patchsets to process.
     *           DEFAULT: None (all patchsets are processed).
     * </pre>
     */
    public function __construct($rep, $opts = array())
    {
        $revs = array();

        if (isset($opts['file'])) {
            $ob = $rep->getFileObject($opts['file']);
            $revs = $ob->queryLogs();
        } elseif (!empty($opts['range'])) {
            foreach ($opts['range'] as $val) {
                /* Grab a filename in the patchset to get log info. */
                $cmd = $rep->getCommand() . ' diff-tree --name-only -r ' . escapeshellarg($val);
                exec($cmd, $output);

                /* The first line is the SHA1 hash. */
                $ob = $rep->getFileObject($output[1]);
                $revs[$val] = $ob->queryLogs($val);
            }
        }

        reset($revs);
        while (list($rev, $log) = each($revs)) {
            if (empty($log)) {
                continue;
            }

            $this->_patchsets[$rev] = array(
                'date' => $log->queryDate(),
                'author' => $log->queryAuthor(),
                'branches' => $log->queryBranch(),
                'tags' => $log->queryTags(),
                'log' => $log->queryLog(),
                'members' => array(),
            );

            foreach ($log->queryFiles() as $file) {
                $to = $rev;
                $status = self::MODIFIED;

                switch ($file['status']) {
                case 'A':
                    $from = null;
                    $status = self::ADDED;
                    break;

                case 'D':
                    $from = $to;
                    $to = null;
                    $status = self::DELETED;
                    break;

                default:
                    $from = $log->queryParent();
                }

                $statinfo = isset($file['added'])
                    ? array('added' => $file['added'], 'deleted' => $file['deleted'])
                    : array();

                $this->_patchsets[$rev]['members'][] = array_merge(array(
                    'file' => $file['srcPath'],
                    'from' => $from,
                    'status' => $status,
                    'to' => $to,
                ), $statinfo);
            }
        }
    }

}
