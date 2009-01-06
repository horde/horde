<?php
/**
 * Horde_Vcs_git implementation.
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
class Horde_Vcs_git extends Horde_Vcs
{
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
            $this->_cache['co'] = new 'Horde_Vcs_Checkout_git';
        }
        return $this->_cache['co']->get($this, $file->queryModulePath(), $rev);
    }

}

/**
 * Horde_Vcs_git annotate class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Annotate_git extends Horde_Vcs_Annotate
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
            return PEAR::raiseError('Failed to execute git annotate: ' . $command);
        }

        $curr_rev = null;
        $db = $lines = array();
        $lines_group = $line_num = 0;

        while (!feof($pipe)) {
            $line = rtrim(fgets($pipe, 4096));

            if (!$line || ($line[0] == "\t")) {
                $lines[] = array(
                    'author' => $db[$curr_rev]['author'] . ' ' . $db[$curr_rev]['author-mail'],
                    'date' => $db[$curr_rev]['author-time'],
                    'line' => $line ? substr($line, 1) : '',
                    'lineno' => $line_num++,
                    'rev' => $curr_rev
                );
                --$lines_group;
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
 * Horde_Vcs_git checkout class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Checkout_git extends Horde_Vcs_Checkout
{
    /**
     * Function which returns a file pointing to the head of the requested
     * revision of an SVN file.
     *
     * @param Horde_Vcs $rep     A repository object
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

        return ($pipe = popen($rep->getCommand() . ' cat-file blob ' . $file_ob->getHashForRevision($rev) . ' 2>&1', VC_WINDOWS ? 'rb' : 'r'))
            ? $pipe
            : PEAR::raiseError('Couldn\'t perform checkout of the requested file');
    }

}

/**
 * Horde_Vcs_git diff class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Diff_git extends Horde_Vcs_Diff
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

        // TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $rep->getCommand() . " diff -M -C $options --no-color $rev1..$rev2 -- \"" . $file->queryModulePath() . '" 2>&1';

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
        $cmd = $rep->getCommand() . ' rev-list ' . $r1 . '..' . $r2 . ' -- "' . $file->queryModulePath() . '"';
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
 * Horde_Vcs_git directory class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_git extends Horde_Vcs_Directory
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
        //@TODO For now, we're browsing HEAD
        $head = trim(shell_exec($this->_rep->getCommand() . ' rev-parse --verify HEAD'));
        //@TODO can use this to see if we have a valid cache of the tree at this revision

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
            return PEAR::raiseError('Failed to execute git ls-tree: ' . $cmd);
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
 * Horde_Vcs_git file class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_git extends Horde_Vcs_File
{
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

        $this->logs = $this->revs = $this->revsym = $this->symrev = $this->branches = array();
    }

    public function getFileObject()
    {
        $this->getBrowseInfo();
        return $this;
    }

    /**
     * Get the hash name for this file at a specific revision.
     *
     * @param string $rev
     *
     * @return string Commit hash
     */
    public function getHashForRevision($rev)
    {
        return $this->logs[$rev]->getHashForPath($this->fullname);
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return Filename without repository extension
     */
    function queryName()
    {
        return $this->name;
    }

    /**
     * Populate the object with information about the revisions logs
     * and dates of the file.
     *
     * @return mixed  True on success, PEAR_Error on error.
     */
    function getBrowseInfo()
    {
        // Get the list of revisions that touch this path
        $Q = VC_WINDOWS ? '"' : "'";
        $cmd = $this->rep->getCommand() . ' rev-list HEAD -- ' . $Q . str_replace($Q, '\\' . $Q, $this->fullname) . $Q . ' 2>&1';
        $revisions = shell_exec($cmd);
        if (substr($revisions, 5) == 'fatal') {
            throw new Horde_Vcs_Exception($revisions);
        }

        if (!strlen($revisions)) {
            throw new Horde_Vcs_Exception('No revisions found');
        }

        $this->revs = explode("\n", trim($revisions));
        foreach ($this->revs as $rev) {
            $this->logs[$rev] = Horde_Vcs_Log_git::factory($this->rep, $this, $rev);
            if ($this->quicklog) {
                break;
            }
        }
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return $this->name;
    }

}

/**
 * Horde_Vcs_git log class.
 *
 * Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_git {

    public $err;
    public $files = array();

    public static function factory($rep, $file, $rev)
    {
        /* The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data. */
        $cacheVersion = 1;
        $cacheId = $rep->sourceroot() . '_r' . $rev . '_v' . $cacheVersion;

        if (0/*@TODO no caching during dev*/ && $rep->cache &&
            // Individual revisions can be cached forever
            $rep->cache->exists($cacheId, 0)) {
            $logOb = unserialize($rep->cache->get($cacheId, 0));
            $logOb->setRepository($rep);
        } else {
            $logOb = new Horde_Vcs_Log_git($rep, $file, $rev);

            if ($rep->cache) {
                $rep->cache->set($cacheId, serialize($logOb));
            }
        }

        return $logOb;
    }

    /**
     * Constructor.
     */
    public function __construct($rep, $fl, $rev)
    {
        parent::__construct($rep, $fl);

        $this->rev = $rev;

        $cmd = $this->rep->getCommand() . ' whatchanged --no-color --pretty=fuller --no-abbrev -n 1 ' . $this->rev;
        $pipe = popen($cmd, 'r');
        if (!is_resource($pipe)) {
            throw new Horde_Vcs_Exception('Unable to run ' . $cmd . ': ' . error_get_last());
        }

        $commit = trim(array_pop(explode(' ', fgets($pipe))));
        if ($commit != $rev) {
            throw new Horde_Vcs_Exception('Expected ' . $rev . ', got ' . $commit);
        }

        $properties = array();
        $line = trim(fgets($pipe));
        while ($line != '') {
            list($key, $value) = explode(':', $line, 2);
            $properties[trim($key)] = trim($value);
            $line = trim(fgets($pipe));
        }

        $this->author = $properties['Author'];
        $this->date = strtotime($properties['AuthorDate']);
        //@TODO use Committer, CommitterDate, and Merge properties

        $log = '';
        $line = fgets($pipe);
        while (substr($line, 0, 1) != ':') {
            $log .= $line;
            $line = fgets($pipe);
        }
        $this->log = trim($log);
        //@TODO internal line formatting

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
    }

    public function setRepository($rep)
    {
        $this->rep = $rep;
    }

    public function getHashForPath($path)
    {
        //@TODO not confident yet abotu the choice of dstSha1 vs. srcSha1
        return $this->files[$path]['dstSha1'];
    }

    /**
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return hash of symbolic names => branch numbers
     */
    public function querySymbolicBranches()
    {
        return array();
    }

}

/**
 * Horde_Vcs_git Patchset class.
 *
 * Copyright Chuck Hagenbuch <chuck@horde.org>
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_git extends Horde_Vcs_Patchset
{
    /**
     * Populate the object with information about the patchsets that
     * this file is involved in.
     *
     * @return mixed  PEAR_Error object on error, or true on success.
     */
    function getPatchsets()
    {
        $fileOb = new Horde_Vcs_File_git($this->_rep, $this->_file);
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
                $action = substr($file, 0, 1);
                $file = preg_replace('/.*?\s(.*?)(\s|$).*/', '\\1', $file);
                $to = $rev;
                if ($action == 'A') {
                    $from = 'INITIAL';
                } elseif ($action == 'D') {
                    $from = $to;
                    $to = '(DEAD)';
                } else {
                    // This technically isn't the previous revision,
                    // but it works for diffing purposes.
                    $from = $to - 1;
                }

                $this->_patchsets[$rev]['members'][] = array('file' => $file,
                                                             'from' => $from,
                                                             'to' => $to);
            }
        }

        return true;
    }

}

class Horde_Vcs_Revision_git extends Horde_Vcs_Revision
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
        return substr($rev, 0, 7);
    }

}
