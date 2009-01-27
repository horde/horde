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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
     * The available diff types.
     *
     * @var array
     */
    protected $_diffTypes = array('unified');

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
    public function isFile($where)
    {
        $where = str_replace($this->sourceroot() . '/', '', $where);
        $command = $this->getCommand() . ' ls-tree master ' . escapeshellarg($where) . ' 2>&1';
        $entry = array();
        exec($command, $entry);
        $data = explode(' ', $entry[0]);
        return ($data[1] == 'blob');
    }

    /**
     * TODO
     */
    public function getCommand()
    {
        return escapeshellcmd($this->getPath('git')) . ' --git-dir=' . escapeshellarg($this->_sourceroot);
    }

    /**
     * TODO
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
     */
    public function checkout($file, $rev)
    {
        $this->assertValidRevision($rev);

        $file_ob = $this->getFileObject($file);

        if ($pipe = popen($this->getCommand() . ' cat-file blob ' . $file_ob->getHashForRevision($rev) . ' 2>&1', VC_WINDOWS ? 'rb' : 'r')) {
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
        $cmd = $this->getCommand() . ' rev-list ' . escapeshellarg($r1) . '..' . escapeshellarg($r2) . ' -- ' . escapeshellarg($file->queryModulePath());
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
     * @return string|boolean  False on failure, or a string containing the
     *                         diff on success.
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
            $flags .= '--unified=' . (int)$opts['num'];
            break;
        }

        // @TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $this->getCommand() . " diff -M -C $flags --no-color " . escapeshellarg($rev1 . '..' . $rev2) . ' -- ' . escapeshellarg($file->queryModulePath()) . ' 2>&1';

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
     * Create a Directory object to store information about the files in a
     * single directory in the repository.
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        // @TODO For now, we're browsing HEAD
        //$head = trim(shell_exec($this->_rep->getCommand() . ' rev-parse --verify master'));
        $head = 'HEAD';
        // @TODO can use this to see if we have a valid cache of the tree at this revision

        $dir = $this->queryDir();
        if (substr($dir, 0, 1) == '/') {
            $dir = substr($dir, 1);
        }
        if (strlen($dir) && substr($dir, -1) != '/') {
            $dir .= '/';
        }

        $cmd = $rep->getCommand() . ' ls-tree --full-name ' . escapeshellarg($head) . ' ' . escapeshellarg($dir) . ' 2>&1';

        $dir = popen($cmd, 'r');
        if (!$dir) {
            throw new Horde_Vcs_Exception('Failed to execute git ls-tree: ' . $cmd);
        }

        // Create two arrays - one of all the files, and the other of
        // all the dirs.
        while (!feof($dir)) {
            $line = chop(fgets($dir, 1024));
            if (!strlen($line)) {
                continue;
            }

            list( ,$type, , $file) = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            if ($type == 'tree') {
                $this->_dirs[] = basename($file);
            } else {
                $this->_files[] = $rep->getFileObject($file, array('quicklog' => !empty($opts['quicklog'])));
            }
        }

        pclose($dir);
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
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param TODO $rep    TODO
     * @param string $fl   Full path to this file.
     * @param array $opts  TODO
     */
    public function __construct($rep, $fl, $opts = array())
    {
        parent::__construct($rep, $fl, $opts);

        // Get the list of revisions that touch this path
        $this->_revs = $this->_getRevList($this->_branch);

        foreach ($this->_revs as $rev) {
            $this->_logs[$rev] = $rep->getLogObject($this, $rev);
            if ($this->_quicklog) {
                break;
            }
        }

        // Add branch information
        $cmd = $rep->getCommand() . ' show-ref --heads';
        $branch_list = shell_exec($cmd);
        if (empty($branch_list)) {
            throw new Horde_Vcs_Exception('No branches found');
        }

        foreach (explode("\n", trim($branch_list)) as $val) {
            $line = explode(' ', trim($val), 2);
            $this->_branches[substr($line[1], strrpos($line[1], '/') + 1)] = $line[0];
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
        return $this->_logs[$rev]->getHashForPath($this->queryModulePath());
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
        $revs = array();

        foreach (array_keys($this->_branches) as $key) {
            $revs[$key] = $this->_getRevList($key);
        }

        return $revs;
    }

    /**
     * TODO
     */
    protected function _getRevList($branch)
    {
        $cmd = $this->_rep->getCommand() . ' rev-list ' . (empty($branch) ? '--branches' : $branch) . ' -- ' . escapeshellarg($this->queryModulePath()) . ' 2>&1';

        $revisions = shell_exec($cmd);
        if (substr($revisions, 5) == 'fatal') {
            throw new Horde_Vcs_Exception($revisions);
        } elseif (!strlen($revisions)) {
            throw new Horde_Vcs_Exception('No revisions found');
        }

        return explode("\n", trim($revisions));
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
     * Constructor.
     */
    public function __construct($rep, $fl, $rev)
    {
        parent::__construct($rep, $fl, $rev);

        $cmd = $rep->getCommand() . ' whatchanged --no-color --pretty=format:"commit %H%nAuthor:%an <%ae>%nAuthorDate:%at%nRefs:%d%n%n%s%n%b" --no-abbrev -n 1 ' . $rev;
        $pipe = popen($cmd, 'r');
        if (!is_resource($pipe)) {
            throw new Horde_Vcs_Exception('Unable to run ' . $cmd . ': ' . error_get_last());
        }

        $commit = trim(array_pop(explode(' ', fgets($pipe))));
        if ($commit != $rev) {
            fclose($pipe);
            throw new Horde_Vcs_Exception('Expected ' . $rev . ', got ' . $commit);
        }

        // @TODO use Commit, CommitDate, and Merge properties
        $line = trim(fgets($pipe));
        while ($line != '') {
            list($key, $value) = explode(':', $line, 2);
            $value = trim($value);

            switch (trim($key)) {
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

            $line = trim(fgets($pipe));
        }


        $log = '';
        $line = fgets($pipe);
        while (substr($line, 0, 1) != ':') {
            $log .= $line;
            $line = fgets($pipe);
        }
        $this->_log = trim($log);
        // @TODO internal line formatting

        // Build list of files in this revision. The format of these lines is
        // documented in the git diff-tree documentation:
        // http://www.kernel.org/pub/software/scm/git/docs/git-diff-tree.html
        while ($line) {
            preg_match('/:(\d+) (\d+) (\w+) (\w+) (.+)\t(.+)(\t(.+))?/', $line, $matches);
            $this->_files[$matches[6]] = array(
                'srcMode' => $matches[1],
                'dstMode' => $matches[2],
                'srcSha1' => $matches[3],
                'dstSha1' => $matches[4],
                'status' => $matches[5],
                'srcPath' => $matches[6],
                'dstPath' => isset($matches[7]) ? $matches[7] : ''
            );

            $line = fgets($pipe);
        }

        fclose($pipe);
    }

    /**
     * TODO
     */
    public function getHashForPath($path)
    {
        // @TODO Not confident yet abotu the choice of dstSha1 vs. srcSha1
        return $this->_files[$path]['dstSha1'];
    }

    /**
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return  Hash of symbolic names => branch numbers
     */
    public function querySymbolicBranches()
    {
        return array();
    }

    public function queryBranch()
    {
        $branches = array();
        $command = $this->_rep->getCommand() . ' branch --contains ' . escapeshellarg($this->_rev) . ' 2>&1';
        exec($command, $branches);
        return array_map('trim', $branches, array_fill(0, count($branches), '* '));
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
     * @param string $file    The filename to create patchsets for.
     */
    public function __construct($rep, $file)
    {
        $fileOb = $rep->getFileObject($this->file);

        foreach ($fileOb->logs as $rev => $log) {
            $this->_patchsets[$rev] = array(
                'date' => $log->queryDate(),
                'author' => $log->queryAuthor(),
                'branch' => '',
                'tag' => '',
                'log' => $log->queryLog(),
                'members' => array()
            );

            foreach ($log->files as $file) {
                $file = preg_replace('/.*?\s(.*?)(\s|$).*/', '\\1', $file);
                $to = $rev;

                switch ($file['status']) {
                case 'A':
                    $from = 'INITIAL';
                    break;

                case 'D':
                    $from = $to;
                    $to = '(DEAD)';
                    break;

                default:
                    // This technically isn't the previous revision,
                    // but it works for diffing purposes.
                    $from = $to - 1;
                }

                $this->_patchsets[$rev]['members'][] = array(
                    'file' => $file,
                    'from' => $from,
                    'to' => $to
                );
            }
        }

        return true;
    }

}
