<?php
/**
 * Horde_Vcs_Git implementation.
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
     * Constructor.
     *
     * @param array $params  Any parameter the class expects.
     *                       Current parameters:
     *                       'sourceroot': The source root for this
     *                                     repository
     *                       'paths': Hash with the locations of all
     *                                necessary binaries: 'git'
     */
    public function __construct($params)
    {
        $this->_sourceroot = escapeshellcmd($params['sourceroot']);
        $this->_paths = $params['paths'];
        parent::__construct();
    }

    public function getCommand()
    {
        return $this->getPath('git') . ' --git-dir=' . $this->_sourceroot;
    }

    public function getCheckout($file, $rev)
    {
        if (!isset($this->_cache['co'])) {
            $this->_cache['co'] = new Horde_Vcs_Checkout_Git();
        }
        return $this->_cache['co']->get($this, $file->queryModulePath(), $rev);
    }

}

/**
 * Horde_Vcs_Git annotate class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Annotate_Git extends Horde_Vcs_Annotate
{
    public function __construct($rep, $file)
    {
        if (is_a($file, 'PEAR_Error')) {
            throw new Horde_Vcs_Exception($file->getMessage());
        }
        parent::__construct($rep, $file);
    }

    /**
     * TODO
     */
    public function doAnnotate($rev)
    {
        $this->_rep->assertValidRevision($rev);

        $command = $this->_rep->getCommand() . ' blame -p ' . $rev . ' -- ' . escapeshellarg($this->_file->queryModulePath()) . ' 2>&1';
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

}

/**
 * Horde_Vcs_Git checkout class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Checkout_Git extends Horde_Vcs_Checkout
{
    /**
     * Function which returns a file pointing to the head of the requested
     * revision of a file.
     *
     * @param Horde_Vcs $rep    A repository object
     * @param string $fullname  Fully qualified pathname of the desired file
     *                          to checkout
     * @param string $rev       Revision number to check out
     *
     * @return resource|object  Either a PEAR_Error object, or a stream
     *                          pointer to the head of the checkout.
     */
    function get($rep, $file, $rev)
    {
        $rep->assertValidRevision($rev);

        $file_ob = $rep->getFileObject($file);

        if ($pipe = popen($rep->getCommand() . ' cat-file blob ' . $file_ob->getHashForRevision($rev) . ' 2>&1', VC_WINDOWS ? 'rb' : 'r')) {
            return $pipe;
        }

        throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
    }

}

/**
 * Horde_Vcs_Git diff class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Diff_Git extends Horde_Vcs_Diff
{
    /**
     * The available diff types.
     *
     * @var array
     */
    protected $_diffTypes = array('unified');

    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_Vcs $rep        A repository object.
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $rev1         Original revision number to compare from.
     * @param string $rev2         New revision number to compare against.
     * @param string $type         The type of diff (e.g. 'unified').
     * @param integer $num         Number of lines to be used in context and
     *                             unified diffs.
     * @param boolean $ws          Show whitespace in the diff?
     *
     * @return string|boolean  False on failure, or a string containing the
     *                         diff on success.
     */
    public function get($rep, $file, $rev1, $rev2, $type = 'context',
                               $num = 3, $ws = true)
    {
        /* Make sure that the file parameter is valid */
        if (is_a($file, 'PEAR_Error')) {
            return false;
        }

        /* Check that the revision numbers are valid */
        $rev1 = $rep->isValidRevision($rev1) ? $rev1 : 0;
        $rev2 = $rep->isValidRevision($rev1) ? $rev2 : 0;

        $diff = array();
        $options = '';
        if (!$ws) {
            $options .= ' -b -w ';
        }

        switch ($type) {
        case 'unified':
            $options .= '--unified=' . (int)$num;
            break;
        }

        // @TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $rep->getCommand() . " diff -M -C $options --no-color $rev1..$rev2 -- " . escapeshellarg($file->queryModulePath()) . ' 2>&1';

        exec($command, $diff, $retval);
        return $diff;
    }

    /**
     * Create a range of revisions between two revision numbers.
     *
     * @param Horde_Vcs $rep        A repository object.
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $r1           The initial revision.
     * @param string $r2           The ending revision.
     *
     * @return array  The revision range, or empty if there is no straight
     *                line path between the revisions.
     */
    public function getRevisionRange($rep, $file, $r1, $r2)
    {
        $revs = $this->_getRevisionRange($rep, $file, $r1, $r2);
        if (empty($revs)) {
            $revs = array_reverse($this->_getRevisionRange($rep, $file, $r2, $r1));
        }
        return $revs;
    }

    private function _getRevisionRange($rep, $file, $r1, $r2)
    {
        $cmd = $rep->getCommand() . ' rev-list ' . $r1 . '..' . $r2 . ' -- ' . escapeshellarg($file->queryModulePath());
        $pipe = popen($cmd, 'r');
        if (!is_resource($pipe)) {
            throw new Horde_Vcs_Exception('Unable to run ' . $cmd . ': ' . error_get_last());
        }

        $revs = array();

        while (!feof($pipe)) {
            if ($rev = trim(fgets($pipe, 4096))) {
                $revs[] = $rev;
            }
        }

        pclose($pipe);

        return $revs;
    }
}

/**
 * Horde_Vcs_Git directory class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Git extends Horde_Vcs_Directory
{
    /**
     * Tell the object to open and browse its current directory, and
     * retrieve a list of all the objects in there.  It then populates
     * the file/directory stack and makes it available for retrieval.
     *
     * @return PEAR_Error object on an error, 1 on success.
     */
    public function browseDir($cache = null, $quicklog = true,
                              $showattic = false)
    {
        // @TODO For now, we're browsing master
        $head = trim(shell_exec($this->_rep->getCommand() . ' rev-parse --verify master'));
        // @TODO can use this to see if we have a valid cache of the tree at this revision

        $dir = $this->queryDir();
        if (substr($dir, 0, 1) == '/') {
            $dir = substr($dir, 1);
        }
        if (strlen($dir) && substr($dir, -1) != '/') {
            $dir .= '/';
        }

        $cmd = $this->_rep->getCommand() . ' ls-tree --full-name ' . $head . ' ' . escapeshellarg($dir) . ' 2>&1';

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
                $this->_files[] = $this->_rep->getFileObject($file, $cache, $quicklog);
            }
        }

        pclose($dir);
    }

}

/**
 * Horde_Vcs_Git file class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_Git extends Horde_Vcs_File
{
    /* @TODO */
    protected $_branch = 'master';

    /**
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param string $fl  Full path to this file.
     */
    public function __construct($rep, $fl, $cache = null, $quicklog = false)
    {
        // FIXME:
        $rep->cache = $cache;

        $this->rep = $rep;
        $this->fullname = $fl;
        $this->name = basename($fl);
        $this->dir = dirname($fl);
        $this->quicklog = $quicklog;
        $this->cache = $cache;
    }

    /**
     * TODO
     */
    public function getFileObject()
    {
        $this->getBrowseInfo();
        return $this;
    }

    /**
     * Get the hash name for this file at a specific revision.
     *
     * @param string $rev  TODO
     *
     * @return string  Commit hash.
     */
    public function getHashForRevision($rev)
    {
        return $this->logs[$rev]->getHashForPath($this->fullname);
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return string  Filename without repository extension
     */
    function queryName()
    {
        return $this->name;
    }

    /**
     * Populate the object with information about the revisions logs
     * and dates of the file.
     */
    function getBrowseInfo()
    {
        // Get the list of revisions that touch this path
        // TODO: Gets all revisions
        $cmd = $this->rep->getCommand() . ' rev-list --branches -- ' . escapeshellarg($this->fullname) . ' 2>&1';
        $revisions = shell_exec($cmd);
        if (substr($revisions, 5) == 'fatal') {
            throw new Horde_Vcs_Exception($revisions);
        }

        if (!strlen($revisions)) {
            throw new Horde_Vcs_Exception('No revisions found');
        }

        $this->revs = explode("\n", trim($revisions));

        foreach ($this->revs as $rev) {
            $this->logs[$rev] = Horde_Vcs_Log_Git::factory($this, $rev);
            if ($this->quicklog) {
                break;
            }
        }

        // Add branch information
        $cmd = $this->rep->getCommand() . ' show-ref --heads';
        $branch_list = shell_exec($cmd);
        if (empty($branch_list)) {
            throw new Horde_Vcs_Exception('No branches found');
        }

        foreach (explode("\n", trim($branch_list)) as $val) {
            $line = explode(' ', trim($val), 2);
            $this->branches[substr($line[1], strrpos($line[1], '/') + 1)] = $line[0];
        }
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return ($this->dir == '.')
            ? $this->name
            : $this->dir . '/' . $this->name;
    }

}

/**
 * Horde_Vcs_Git log class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Git extends Horde_Vcs_Log
{
    public $files = array();

    public static function factory($file, $rev)
    {
        /* The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data. */
        $cacheVersion = 1;
        $cacheId = $file->rep->sourceroot() . '_r' . $rev . '_v' . $cacheVersion;

        if ($file->rep->cache &&
            // Individual revisions can be cached forever
            // return array_keys(
            $file->rep->cache->exists($cacheId, 0)) {
            $logOb = unserialize($file->rep->cache->get($cacheId, 0));
            $logOb->setRepository($file->rep);
        } else {
            $logOb = new Horde_Vcs_Log_Git($file, $rev);

            if ($file->rep->cache) {
                $file->rep->cache->set($cacheId, serialize($logOb));
            }
        }

        return $logOb;
    }

    /**
     * Constructor.
     */
    public function __construct($fl, $rev)
    {
        parent::__construct($fl);

        $this->rev = $rev;

        $cmd = $this->rep->getCommand() . ' whatchanged --no-color --pretty=format:"commit %H%nAuthor:%an <%ae>%nAuthorDate:%at%nRefs:%d%n%n%s%n%b" --no-abbrev -n 1 ' . $this->rev;
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
                $this->author = $value;
                break;

            case 'AuthorDate':
                $this->date = $value;
                break;

            case 'Refs':
                if ($value) {
                    $value = substr($value, 1, -1);
                    foreach (explode(',', $value) as $val) {
                        $val = trim($val);
                        if (strpos($val, 'refs/tags/') === 0) {
                            $this->tags[] = substr($val, 10);
                        }
                    }
                    if (!empty($this->tags)) {
                        sort($this->tags);
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
        $this->log = trim($log);
        // @TODO internal line formatting

        // Build list of files in this revision. The format of these lines is
        // documented in the git diff-tree documentation:
        // http://www.kernel.org/pub/software/scm/git/docs/git-diff-tree.html
        while ($line) {
            preg_match('/:(\d+) (\d+) (\w+) (\w+) (.+)\t(.+)(\t(.+))?/', $line, $matches);
            $this->files[$matches[6]] = array(
                'srcMode' => $matches[1],
                'dstMode' => $matches[2],
                'srcSha1' => $matches[3],
                'dstSha1' => $matches[4],
                'status' => $matches[5],
                'srcPath' => $matches[6],
                'dstPath' => isset($matches[7]) ? $matches[7] : '',
            );

            $line = fgets($pipe);
        }

        fclose($pipe);
    }

    public function setRepository($rep)
    {
        $this->rep = $rep;
    }

    public function getHashForPath($path)
    {
        // @TODO not confident yet abotu the choice of dstSha1 vs. srcSha1
        return $this->files[$path]['dstSha1'];
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
        $branches = $ret = array();
        $command = $this->rep->getCommand() . ' branch --contains ' . escapeshellarg($this->rev) . ' 2>&1';
        exec($command, $branches);
        return array_map('trim', $branches, array_fill(0, count($branches), '* '));
    }

}

/**
 * Horde_Vcs_Git Patchset class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_Git extends Horde_Vcs_Patchset
{
    /**
     * Populate the object with information about the patchsets that
     * this file is involved in.
     *
     * @return mixed  PEAR_Error object on error, or true on success.
     */
    function getPatchsets()
    {
        $fileOb = new Horde_Vcs_File_Git($this->_rep, $this->_file);
        if (is_a(($result = $fileOb->getBrowseInfo()), 'PEAR_Error')) {
            return $result;
        }

        $this->_patchsets = array();

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

class Horde_Vcs_Revision_Git extends Horde_Vcs_Revision
{
    /**
     * Validation function to ensure that a revision number is of the right
     * form.
     *
     * @param mixed $rev  The purported revision number.
     *
     * @return boolean  True if it is a revision number.
     */
    public function valid($rev)
    {
        return preg_match('/^[a-f0-9]+$/i', $rev);
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
