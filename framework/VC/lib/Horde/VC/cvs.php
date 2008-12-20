<?php

require_once dirname(__FILE__) . '/rcs.php';

/**
 * Horde_VC_cvs implementation.
 *
 * Copyright 2000-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_cvs extends Horde_VC_rcs
{
    /**
     * Constructor.
     *
     * @param array $params  Any parameter the class expects.
     *                       Current parameters:
     * <pre>
     * 'sourceroot': The source root for this repository
     * 'paths': Hash with the locations of all necessary binaries: 'rcsdiff',
     *          'rlog', 'cvsps', 'cvsps_home' and the temp path: 'temp'
     * </pre>
     */
    public function __construct($params)
    {
        $this->_sourceroot = $params['sourceroot'];
        $this->_paths = $params['paths'];
        parent::__construct();
    }

    /**
     * Returns the temporary file path.
     *
     * @return string  Temporary file path.
     */
    public function getTempPath()
    {
        return $this->_paths['temp'];
    }

    /**
     * TODO
     */
    public function isFile($where)
    {
        return @is_file($where . ',v') ||
               @is_file(dirname($where) . '/Attic/' . basename($where) . ',v');
    }

    /**
     * TODO
     */
    public function getFileObject($filename, $cache = null, $quicklog = false)
    {
        if (substr($filename, 0, 1) != '/') {
            $filename = '/' . $filename;
        }
        return parent::getFileObject($this->sourceroot() . $filename, $cache, $quicklog);
    }

    /**
     * TODO
     */
    public function getAnnotateObject($filename)
    {
        return new Horde_VC_Annotate_cvs($this, $filename);
    }

    /**
     * TODO
     */
    public function getPatchsetObject($filename, $cache = null)
    {
        return parent::getPatchsetObject($this->sourceroot() . '/' . $filename, $cache);
    }

    /**
     * Validation function to ensure that a revision number is of the right
     * form.
     *
     * @param mixed $rev  The purported revision number.
     *
     * @return boolean  True if it is a revision number.
     */
    public function isValidRevision($rev)
    {
	    return $rev && preg_match('/^[\d\.]+$/', $rev);
    }
}

/**
 * Horde_VC_cvs annotate class.
 *
 * Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Annotate_cvs extends Horde_VC_Annotate
{
    /**
     * Temporary filename.
     *
     * @var string
     */
    protected $_tmpfile;

    /**
     * Constructor.
     *
     * TODO
     */
    public function __construct($rep, $file)
    {
        $this->_tmpfile = Util::getTempFile('vc', true, $rep->getTempPath());
        parent::__construct($rep, $file);
    }

    /**
     * TODO
     */
    public function doAnnotate($rev)
    {
        if (is_a($this->_file, 'PEAR_Error') ||
            is_a($this->_rep, 'PEAR_Error') ||
            !$this->_rep->isValidRevision($rev)) {
            return false;
        }

        $where = $this->_file->queryModulePath();
        $sourceroot = $this->_rep->sourceroot();

        $pipe = popen($this->_rep->getPath('cvs') . ' -n server > ' . $this->_tmpfile, VC_WINDOWS ? 'wb' : 'w');

        $out = array(
            "Root $sourceroot",
            'Valid-responses ok error Valid-requests Checked-in Updated Merged Removed M E',
            'UseUnchanged',
            'Argument -r',
            "Argument $rev",
            "Argument $where"
        );

        $dirs = explode('/', dirname($where));
        while (count($dirs)) {
            $out[] = 'Directory ' . implode('/', $dirs);
            $out[] = "$sourceroot/" . implode('/', $dirs);
            array_pop($dirs);
        }

        $out[] = 'Directory .';
        $out[] = $sourceroot;
        $out[] = 'annotate';

        foreach ($out as $line) {
            fwrite($pipe, "$line\n");
        }
        pclose($pipe);

        if (!($fl = fopen($this->_tmpfile, VC_WINDOWS ? 'rb' : 'r'))) {
            return false;
        }

        $lines = array();
        $line = fgets($fl, 4096);

        // Windows versions of cvs always return $where with forwards slashes.
        if (VC_WINDOWS) {
            $where = str_replace(DIRECTORY_SEPARATOR, '/', $where);
        }

        while ($line && !preg_match("|^E\s+Annotations for $where|", $line)) {
            $line = fgets($fl, 4096);
        }

        if (!$line) {
            return PEAR::raiseError('Unable to annotate; server said: ' . $line);
        }

        $lineno = 1;
        while ($line = fgets($fl, 4096)) {
            if (preg_match('/^M\s+([\d\.]+)\s+\((.+)\s+(\d+-\w+-\d+)\):.(.*)$/', $line, $regs)) {
                $lines[] = array(
                    'rev' => $regs[1],
                    'author' => trim($regs[2]),
                    'date' => $regs[3],
                    'line' => $regs[4],
                    'lineno' => $lineno++
                );
            }
        }

        fclose($fl);
        return $lines;
    }

}

/**
 * Horde_VC_cvs checkout class.
 *
 * Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Checkout_cvs extends Horde_VC_Checkout
{
    /**
     * Static function which returns a file pointing to the head of the
     * requested revision of an RCS file.
     *
     * @param Horde_VC_cvs $rep  A repository object
     * @param string $fullname   Fully qualified pathname of the desired file
     *                           to checkout
     * @param string $rev        Revision number to check out
     *
     * @return resource|object  Either a PEAR_Error object, or a stream
     *                          pointer to the head of the checkout
     */
    public function get($rep, $fullname, $rev)
    {
        if (!$rep->isValidRevision($rev)) {
            return PEAR::raiseError('Invalid revision number');
        }

        if (VC_WINDOWS) {
            $mode = 'rb';
            $q_name = '"' . escapeshellcmd(str_replace('\\', '/', $fullname)) . '"';
        } else {
            $mode = 'r';
            $q_name = escapeshellarg($fullname);
        }

        if (!($RCS = popen($rep->getPath('co') . " -p$rev $q_name 2>&1", $mode))) {
            return PEAR::raiseError('Couldn\'t perform checkout of the requested file');
        }

        /* First line from co should be of the form :
         * /path/to/filename,v  -->  standard out
         * and we check that this is the case and error otherwise
         */

        $co = fgets($RCS, 1024);
        if (!preg_match('/^([\S ]+),v\s+-->\s+st(andar)?d ?out(put)?\s*$/', $co, $regs) || $regs[1].',v' != $fullname) {
            return PEAR::raiseError('Unexpected output from checkout: ' . $co);
        }

        /* Next line from co is of the form:
         * revision 1.2.3
         * TODO: compare this to $rev for consistency, atm we just
         *       discard the value to move input pointer along - avsm
         */
        $co = fgets($RCS, 1024);

        return $RCS;
    }

}

/**
 * Horde_VC_cvs diff class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Diff_cvs extends Horde_VC_Diff
{
    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_VC $rep        A repository object.
     * @param Horde_VC_File $file  The desired file.
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
        /* Make sure that the file parameter is valid. */
        if (is_a($file, 'PEAR_Error')) {
            return false;
        }

        /* Check that the revision numbers are valid. */
        $rev1 = $rep->isValidRevision($rev1) ? $rev1 : '1.1';
        $rev2 = $rep->isValidRevision($rev1) ? $rev2 : '1.1';

        $fullName = $file->queryFullPath();
        $diff = array();
        $options = '-kk ';
        if (!$ws) {
            $opts = ' -bB ';
            $options .= $opts;
        } else {
            $opts = '';
        }

        switch ($type) {
        case 'context':
            $options = $opts . '-p --context=' . (int)$num;
            break;

        case 'unified':
            $options = $opts . '-p --unified=' . (int)$num;
            break;

        case 'column':
            $options = $opts . '--side-by-side --width=120';
            break;

        case 'ed':
            $options = $opts . '-e';
            break;
        }

        // Windows versions of cvs always return $where with forwards slashes.
        if (VC_WINDOWS) {
            $fullName = str_replace(DIRECTORY_SEPARATOR, '/', $fullName);
        }

        // TODO: add options for $hr options - however these may not be
        // compatible with some diffs.
        $command = $rep->getPath('rcsdiff') . " $options -r$rev1 -r$rev2 \"" . escapeshellcmd($fullName) . '" 2>&1';
        if (VC_WINDOWS) {
            $command .= ' < "' . __FILE__ . '"';
        }

        exec($command, $diff, $retval);
        return ($retval > 0) ? $diff : array();
    }

}

/**
 * Horde_VC_cvs directory class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Directory_cvs extends Horde_VC_Directory
{
    /**
     * Creates a CVS Directory object to store information
     * about the files in a single directory in the repository.
     *
     * @param Horde_VC $rep           A repository object
     * @param string $dn              Path to the directory.
     * @param Horde_VC_Directory $pn  The parent Directory object to this one.
     */
    public function __construct($rep, $dn, $pn = '')
    {
        parent::__construct($rep, $dn, $pn);
        $this->_dirName = $rep->sourceroot() . "/$dn";
    }

    /**
     * Tell the object to open and browse its current directory, and
     * retrieve a list of all the objects in there.  It then populates
     * the file/directory stack and makes it available for retrieval.
     *
     * @return boolean|object  PEAR_Error object on an error, true on success.
     */
    public function browseDir($cache = null, $quicklog = true,
                              $showattic = false)
    {
        /* Make sure we are trying to list a directory */
        if (!@is_dir($this->_dirName)) {
            return PEAR::raiseError('Unable to find directory ' . $this->_dirName);
        }

        /* Open the directory for reading its contents */
        if (!($DIR = @opendir($this->_dirName))) {
            return PEAR::raiseError(empty($php_errormsg) ? 'Permission denied' : $php_errormsg);
        }

        /* Create two arrays - one of all the files, and the other of
         * all the directories. */
        while (($name = readdir($DIR)) !== false) {
            if (($name == '.') || ($name == '..')) {
                continue;
            }

            $path = $this->_dirName . '/' . $name;
            if (@is_dir($path)) {
                /* Skip Attic directory. */
                if ($name != 'Attic') {
                    $this->_dirs[] = $name;
                }
            } elseif (@is_file($path) && (substr($name, -2) == ',v')) {
                /* Spawn a new file object to represent this file. */
                $fl = $this->_rep->getFileObject(substr($path, strlen($this->_rep->sourceroot()), -2), $cache, $quicklog);
                if (is_a($fl, 'PEAR_Error')) {
                    return $fl;
                } else {
                    $this->_files[] = $fl;
                }
            }
        }

        /* Close the filehandle; we've now got a list of dirs and files. */
        closedir($DIR);

        /* If we want to merge the attic, add it in here. */
        if ($showattic) {
            $atticDir = new Horde_VC_Directory_cvs($this->_rep, $this->_moduleName . '/Attic', $this);
            if (!is_a($atticDir->browseDir($cache, $quicklog), 'PEAR_Error')) {
                $this->_atticFiles = $atticDir->queryFileList();
                $this->_mergedFiles = array_merge($this->_files, $this->_atticFiles);
            }
        }

        return true;
    }

}

/**
 * Horde_VC_cvs file class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_File_cvs extends Horde_VC_File
{
    /**
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param string $fl  Full path to this file.
     */
    public function __construct($rep, $fl, $cache = null, $quicklog = false)
    {
        $fl .= ',v';
        $this->rep = $rep;
        $this->name = basename($fl);
        $this->dir = dirname($fl);
        $this->filename = $fl;
        $this->cache = $cache;
        $this->quicklog = $quicklog;
        $this->logs = $this->revs = $this->branches = array();
    }

    function &getFileObject()
    {
        /* Assume file is in the Attic if it doesn't exist. */
        $filename = $this->filename;
        if (!@is_file($filename . ',v')) {
            $filename = dirname($filename) . '/Attic/' . basename($filename);
        }

        /* The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data. */
        $cacheVersion = 2;
        $cacheId = $this->rep->sourceroot() . '_n' . $filename . '_f' . (int)$this->quicklog . '_v' . $cacheVersion;

        $ctime = time() - filemtime($filename . ',v');
        if ($this->cache &&
            $this->cache->exists($cacheId, $ctime)) {
            $fileOb = unserialize($this->cache->get($cacheId, $ctime));
            $fileOb->setRepository($this->rep);
        } else {
            $fileOb = new Horde_VC_File_cvs($this->rep, $filename, $this->cache, $this->quicklog);
            $fileOb->setRepository($this->rep);
            if (is_a(($result = $fileOb->getBrowseInfo()), 'PEAR_Error')) {
                return $result;
            }
            $fileOb->applySort(Horde_VC::SORT_AGE);

            if ($this->cache) {
                $this->cache->set($cacheId, serialize($fileOb));
            }
        }

        return $fileOb;
    }

    /**
     * If this file is present in an Attic directory, this indicates it.
     *
     * @return boolean  True if file is in the Attic, and false otherwise
     */
    function isDeleted()
    {
        return substr($this->dir, -5) == 'Attic';
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return string  Filename without repository extension
     */
    function queryName()
    {
        return preg_replace('/,v$/', '', $this->name);
    }


    function queryPreviousRevision($rev)
    {
        $ob = $this->rep->getRevisionObject();
        return $ob->prev($rev);
    }

    /**
     * Return the HEAD (most recent) revision number for this file.
     *
     * @return string  HEAD revision number
     */
    function queryHead()
    {
        return $this->head;
    }

    /**
     * Populate the object with information about the revisions logs and dates
     * of the file.
     *
     * @return boolean|object  PEAR_Error object on error, or true on success
     */
    function getBrowseInfo()
    {
        /* Check that we are actually in the filesystem. */
        $file = $this->queryFullPath();
        if (!is_file($file)) {
            return PEAR::raiseError('File Not Found: ' . $file);
        }

        /* Call the RCS rlog command to retrieve the file
         * information. */
        $flag = $this->quicklog ? ' -r ' : ' ';
        $q_file = VC_WINDOWS ? '"' . escapeshellcmd($file) . '"' : escapeshellarg($file);

        $cmd = $this->rep->getPath('rlog') . $flag . $q_file;
        exec($cmd, $return_array, $retval);

        if ($retval) {
            return PEAR::raiseError('Failed to spawn rlog to retrieve file log information for ' . $file);
        }

        $accum = array();
        $symrev = array();
        $revsym = array();
        $state = 'init';
        foreach ($return_array as $line) {
            switch ($state) {
            case 'init':
                if (!strncmp('head: ', $line, 6)) {
                    $this->head = substr($line, 6);
                } elseif (!strncmp('branch:', $line, 7)) {
                    $state = 'rev';
                }
                break;

            case 'rev':
                if (!strncmp('----------', $line, 10)) {
                    $state = 'info';
                    $this->symrev = $symrev;
                    $this->revsym = $revsym;
                } elseif (preg_match("/^\s+([^:]+):\s+([\d\.]+)/", $line, $regs)) {
                    // Check to see if this is a branch
                    if (preg_match('/^(\d+(\.\d+)+)\.0\.(\d+)$/', $regs[2])) {
                        $branchRev = $this->toBranch($regs[2]);
                        if (!isset($this->branches[$branchRev])) {
                            $this->branches[$branchRev] = $regs[1];
                        }
                    } else {
                        $symrev[$regs[1]] = $regs[2];
                        if (empty($revsym[$regs[2]])) {
                            $revsym[$regs[2]] = array();
                        }
                        $revsym[$regs[2]][] = $regs[1];
                    }
                }
                break;

            case 'info':
                if (strncmp('==============================', $line, 30) &&
                    strcmp('----------------------------', $line)) {
                    $accum[] = $line;
                } elseif (count($accum)) {
                    // spawn a new Horde_VC_log object and add it to the logs
                    // hash
                    $log = new Horde_VC_Log_cvs($this);
                    $err = $log->processLog($accum);
                    // TODO: error checks - avsm
                    $this->logs[$log->queryRevision()] = $log;
                    $this->revs[] = $log->queryRevision();
                    $accum = array();
                }
                break;
            }
        }

        return true;
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return Fully qualified filename of this object
     */
    function queryFullPath()
    {
        return $this->dir . '/' . $this->name;
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    function queryModulePath()
    {
        return preg_replace('|^'. $this->rep->sourceroot() . '/?(.*),v$|', '\1', $this->queryFullPath());
    }

    /**
     * Given a revision number of the form x.y.0.z, this remaps it
     * into the appropriate branch number, which is x.y.z
     *
     * @param string $rev  Even-digit revision number of a branch
     *
     * @return string  Odd-digit Branch number
     */
    function toBranch($rev)
    {
        /* Check if we have a valid revision number */
        $rev_ob = $this->rep->getRevisionObject();
        if (!$rev_ob->valid($rev)) {
            return false;
        }

        if (($end = strrpos($rev, '.')) === false) {
            return false;
        }

        $rev[$end] = 0;
        if (($end2 = strrpos($rev, '.')) === false) {
            return substr($rev, ++$end);
        }

        return substr_replace($rev, '.', $end2, ($end - $end2 + 1));
    }

}

/**
 * Horde_VC_cvs log class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Log_cvs {

    var $rep;
    var $file;
    var $tags;
    var $rev;
    var $date;
    var $log;
    var $author;
    var $state;
    var $lines;
    var $branches;

    /**
     *
     */
    public function __construct($fl)
    {
        $this->file = $fl;
        $this->branches = array();
    }

    function processLog($raw)
    {
        /* Initialise a simple state machine to parse the output of rlog */
        $state = 'init';
        while (!empty($raw) && $state != 'done') {
            switch ($state) {
            /* Found filename, now looking for the revision number */
            case 'init':
                $line = array_shift($raw);
                if (preg_match("/revision (.+)$/", $line, $parts)) {
                    $this->rev = $parts[1];
                    $state = 'date';
                }
                break;

            /* Found revision and filename, now looking for date */
            case 'date':
                $line = array_shift($raw);
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+([0-9\s+-]+))?|", $line, $parts)) {
                    $this->date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $this->author = $parts[7];
                    $this->state = $parts[8];
                    $this->lines = isset($parts[10]) ? $parts[10] : '';
                    $state = 'branches';
                }
                break;

            /* Look for a branch point here - format is 'branches:
             * x.y.z; a.b.c;' */
            case 'branches':
                /* If we find a branch tag, process and pop it,
                   otherwise leave input stream untouched */
                if (!empty($raw) && preg_match("/^branches:\s+(.*)/", $raw[0], $br)) {
                    /* Get the list of branches from the string, and
                     * push valid revisions into the branches array */
                    $brs = preg_split('/;\s*/', $br[1]);
                    foreach ($brs as $brpoint) {
                        //@TODO
                        //if (Horde_VC_Revision::valid($brpoint)) {
                            $this->branches[] = $brpoint;
                        //}
                    }
                    array_shift($raw);
                }

                $state = 'done';
                break;
            }
        }

        /* Assume the rest of the lines are the log message */
        $this->log = implode("\n", $raw);
        $this->tags = isset($this->file->revsym[$this->rev]) ?
            $this->file->revsym[$this->rev] :
            array();
    }

    function queryDate()
    {
        return $this->date;
    }

    function queryRevision()
    {
        return $this->rev;
    }

    function queryAuthor()
    {
        return $this->author;
    }

    function queryLog()
    {
        return $this->log;
    }

    function queryChangedLines()
    {
        return isset($this->lines) ? ($this->lines) : '';
    }

    /**
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return array  Hash of symbolic names => branch numbers
     */
    function querySymbolicBranches()
    {
        $symBranches = array();
        foreach ($this->branches as $branch) {
            $parts = explode('.', $branch);
            $last = array_pop($parts);
            $parts[] = '0';
            $parts[] = $last;
            $rev = implode('.', $parts);
            if (isset($this->file->branches[$branch])) {
                $symBranches[$this->file->branches[$branch]] = $branch;
            }
        }
        return $symBranches;
    }

}

/**
 * Horde_VC_cvs Patchset class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_VC_Patchset_cvs extends Horde_VC_Patchset {

    var $_dir;
    var $_name;

    /**
     * Create a patchset object.
     *
     * @param string $file  The filename to get patchsets for.
     */
    public function __construct($file)
    {
        $this->_name = basename($file);
        $this->_dir = dirname($file);
    }

    function &getPatchsetObject($rep, $filename, $cache = null)
    {
        /* The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data. */
        $cacheVersion = 1;
        $cacheId = $rep->sourceroot() . '_n' . $filename . '_f_v' . $cacheVersion;

        $ctime = time() - filemtime($filename . ',v');
        if ($cache &&
            $cache->exists($cacheId, $ctime)) {
            $psOb = unserialize($cache->get($cacheId, $ctime));
            $psOb->setRepository($rep);
        } else {
            $psOb = new Horde_VC_Patchset_cvs($filename);
            $psOb->setRepository($rep);
            if (is_a(($result = $psOb->getPatchsets()), 'PEAR_Error')) {
                return $result;
            }

            if ($cache) {
                $cache->set($cacheId, serialize($psOb));
            }
        }

        return $psOb;
    }

    /**
     * Populate the object with information about the patchsets that
     * this file is involved in.
     *
     * @return boolean|object  PEAR_Error object on error, or true on success.
     */
    function getPatchsets()
    {
        /* Check that we are actually in the filesystem. */
        if (!is_file($this->getFullPath() . ',v')) {
            return PEAR::raiseError('File Not Found');
        }

        /* Call cvsps to retrieve all patchsets for this file. */
        $q_root = $this->_rep->sourceroot();
        $q_root = VC_WINDOWS ? '"' . escapeshellcmd($q_root) . '"' : escapeshellarg($q_root);

        $cvsps_home = $this->_rep->getPath('cvsps_home');
        $HOME = !empty($cvsps_home) ?
            'HOME=' . $cvsps_home . ' ' :
            '';

        $cmd = $HOME . $this->_rep->getPath('cvsps') . ' -u --cvs-direct --root ' . $q_root . ' -f ' . escapeshellarg($this->_name) . ' ' . escapeshellarg($this->_dir);
        exec($cmd, $return_array, $retval);
        if ($retval) {
            return PEAR::raiseError('Failed to spawn cvsps to retrieve patchset information');
        }

        $this->_patchsets = array();
        $state = 'begin';
        foreach ($return_array as $line) {
            $line = trim($line);
            if ($line == '---------------------') {
                $state = 'begin';
                continue;
            }

            switch ($state) {
            case 'begin':
                $id = str_replace('PatchSet ', '', $line);
                $this->_patchsets[$id] = array();
                $state = 'info';
                break;

            case 'info':
                $info = explode(':', $line, 2);
                switch ($info[0]) {
                case 'Date':
                    if (preg_match('|(\d{4})/(\d{2})/(\d{2}) (\d{2}):(\d{2}):(\d{2})|', $info[1], $date)) {
                        $this->_patchsets[$id]['date'] = gmmktime($date[4], $date[5], $date[6], $date[2], $date[3], $date[1]);
                    }
                    break;

                case 'Author':
                    $this->_patchsets[$id]['author'] = trim($info[1]);
                    break;

                case 'Branch':
                    if (trim($info[1]) != 'HEAD') {
                        $this->_patchsets[$id]['branch'] = trim($info[1]);
                    }
                    break;

                case 'Tag':
                    if (trim($info[1]) != '(none)') {
                        $this->_patchsets[$id]['tag'] = trim($info[1]);
                    }
                    break;

                case 'Log':
                    $state = 'log';
                    $this->_patchsets[$id]['log'] = '';
                    break;
                }
                break;

            case 'log':
                if ($line == 'Members:') {
                    $state = 'members';
                    $this->_patchsets[$id]['log'] = trim($this->_patchsets[$id]['log']);
                    $this->_patchsets[$id]['members'] = array();
                } else {
                    $this->_patchsets[$id]['log'] .= $line . "\n";
                }
                break;

            case 'members':
                if (!empty($line)) {
                    $parts = explode(':', $line);
                    $revs = explode('->', $parts[1]);
                    $this->_patchsets[$id]['members'][] = array('file' => $parts[0],
                                                                'from' => $revs[0],
                                                                'to' => $revs[1]);
                }
                break;
            }
        }

        return true;
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return string  Fully qualified filename of this object
     */
    function getFullPath()
    {
        return $this->_dir . '/' . $this->_name;
    }

}

class Horde_VC_Revision_cvs extends Horde_VC_Revision_rcs {}
