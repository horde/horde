<?php
/* Need to define this outside of class since constants in class can not be
 * assigned from a function return. */
define('VC_WINDOWS', !strncasecmp(PHP_OS, 'WIN', 3));

/**
 * Version Control generalized library.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde_Vcs
 */
class Horde_Vcs
{
    /* Sorting options */
    const SORT_NONE = 0;    // don't sort
    const SORT_AGE = 1;     // sort by age
    const SORT_NAME = 2;    // sort by filename
    const SORT_REV = 3;     // sort by revision number
    const SORT_AUTHOR = 4;  // sort by author name

    const SORT_ASCENDING = 0;   // ascending order
    const SORT_DESCENDING = 1;  // descending order

    /**
     * The source root of the repository.
     *
     * @var string
     */
    protected $_sourceroot;

    /**
     * Hash with the locations of all necessary binaries.
     *
     * @var array
     */
    protected $_paths = array();

    /**
     * Hash caching the parsed users file.
     *
     * @var array
     */
    protected $_users = array();

    /**
     * The current driver.
     *
     * @var string
     */
    protected $_driver;

    /**
     * If caching is desired, a Horde_Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Does driver support deleted files?
     *
     * @var boolean
     */
    protected $_deleted = false;

    /**
     * Does driver support patchsets?
     *
     * @var boolean
     */
    protected $_patchsets = false;

    /**
     * Does driver support branches?
     *
     * @var boolean
     */
    protected $_branches = false;

    /**
     * Does driver support snapshots?
     *
     * @var boolean
     */
    protected $_snapshots = false;

    /**
     * Current cache version.
     *
     * @var integer
     */
    protected $_cacheVersion = 3;

    /**
     * The available diff types.
     *
     * @var array
     */
    protected $_diffTypes = array('column', 'context', 'ed', 'unified');

    /**
     * Attempts to return a concrete Horde_Vcs instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Vcs subclass to return.
     *                       The code is dynamically included.
     * @param array $params  A hash containing any additional configuration
     *                       or  parameters a subclass might need.
     *
     * @return Horde_Vcs  The newly created concrete instance.
     * @throws Horde_Vcs_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Horde_Vcs_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Vcs_Exception($class . ' not found.');
    }

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        $this->_cache = empty($params['cache']) ? null : $params['cache'];
        $this->_sourceroot = $params['sourceroot'];
        $this->_paths = $params['paths'];

        $pos = strrpos(get_class($this), '_');
        $this->_driver = substr(get_class($this), $pos + 1);
    }

    /**
     * Does this driver support the given feature?
     *
     * @return boolean  True if driver supports the given feature.
     */
    public function hasFeature($feature)
    {
        switch ($feature) {
        case 'branches':
            return $this->_branches;

        case 'deleted':
            return $this->_deleted;

        case 'patchsets':
            return $this->_patchsets;

        case 'snapshots':
            return $this->_snapshots;

        default:
            return false;
        }
    }

    /**
     * Return the source root for this repository, with no trailing /.
     *
     * @return string  Source root for this repository.
     */
    public function sourceroot()
    {
        return $this->_sourceroot;
    }

    /**
     * Validation function to ensure that a revision string is of the right
     * form.
     *
     * @param mixed $rev  The purported revision string.
     *
     * @return boolean  True if a valid revision string.
     */
    public function isValidRevision($rev)
    {
        return true;
    }

    /**
     * Throw an exception if the revision number isn't valid.
     *
     * @param mixed $rev  The revision number.
     *
     * @throws Horde_Vcs_Exception
     */
    public function assertValidRevision($rev)
    {
        if (!$this->isValidRevision($rev)) {
            throw new Horde_Vcs_Exception('Invalid revision number');
        }
    }

    /**
     * Given two revisions, this figures out which one is greater than the
     * the other.
     *
     * @param string $rev1  Revision string.
     * @param string $rev2  Second revision string.
     *
     * @return integer  1 if the first is greater, -1 if the second if greater,
     *                  and 0 if they are equal
     */
    public function cmp($rev1, $rev2)
    {
        return strcasecmp($rev1, $rev2);
    }

    /**
     * TODO
     */
    public function isFile($where)
    {
        return true;
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
        return array();
    }

    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $rev1          Original revision number to compare from.
     * @param string $rev2          New revision number to compare against.
     * @param array $opts           The following optional options:
     * <pre>
     * 'human' - (boolean) DEFAULT: false
     * 'num' - (integer) DEFAULT: 3
     * 'type' - (string) DEFAULT: 'unified'
     * 'ws' - (boolean) DEFAULT: true
     * </pre>
     *
     * @return string  The diff string.
     * @throws Horde_Vcs_Exception
     */
    public function diff($file, $rev1, $rev2, $opts = array())
    {
        $opts = array_merge(array(
            'num' => 3,
            'type' => 'unified',
            'ws' => true
        ), $opts);

        $this->assertValidRevision($rev1);
        $this->assertValidRevision($rev2);

        $diff = $this->_diff($file, $rev1, $rev2, $opts);
        return empty($opts['human'])
            ? $diff
            : $this->_humanReadableDiff($diff);
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
        return false;
    }

    /**
     * Obtain a tree containing information about the changes between
     * two revisions.
     *
     * @param array $raw  An array of lines of the raw unified diff,
     *                    normally obtained through Horde_Vcs_Diff::get().
     *
     * @return array  @TODO
     */
    protected function _humanReadableDiff($raw)
    {
        $ret = array();

        /* Hold the left and right columns of lines for change
         * blocks. */
        $cols = array(array(), array());
        $state = 'empty';

        /* Iterate through every line of the diff. */
        foreach ($raw as $line) {
            /* Look for a header which indicates the start of a diff
             * chunk. */
            if (preg_match('/^@@ \-([0-9]+).*\+([0-9]+).*@@(.*)/', $line, $regs)) {
                /* Push any previous header information to the return
                 * stack. */
                if (isset($data)) {
                    $ret[] = $data;
                }
                $data = array('type' => 'header', 'oldline' => $regs[1],
                              'newline' => $regs[2], 'contents'> array());
                $data['function'] = isset($regs[3]) ? $regs[3] : '';
                $state = 'dump';
            } elseif ($state != 'empty') {
                /* We are in a chunk, so split out the action (+/-)
                 * and the line. */
                preg_match('/^([\+\- ])(.*)/', $line, $regs);
                if (count($regs) > 2) {
                    $action = $regs[1];
                    $content = $regs[2];
                } else {
                    $action = ' ';
                    $content = '';
                }

                if ($action == '+') {
                    /* This is just an addition line. */
                    if ($state == 'dump' || $state == 'add') {
                        /* Start adding to the addition stack. */
                        $cols[0][] = $content;
                        $state = 'add';
                    } else {
                        /* This is inside a change block, so start
                         * accumulating lines. */
                        $state = 'change';
                        $cols[1][] = $content;
                    }
                } elseif ($action == '-') {
                    /* This is a removal line. */
                    $state = 'remove';
                    $cols[0][] = $content;
                } else {
                    /* An empty block with no action. */
                    switch ($state) {
                    case 'add':
                        $data['contents'][] = array('type' => 'add', 'lines' => $cols[0]);
                        break;

                    case 'remove':
                        /* We have some removal lines pending in our
                         * stack, so flush them. */
                        $data['contents'][] = array('type' => 'remove', 'lines' => $cols[0]);
                        break;

                    case 'change':
                        /* We have both remove and addition lines, so
                         * this is a change block. */
                        $data['contents'][] = array('type' => 'change', 'old' => $cols[0], 'new' => $cols[1]);
                        break;
                    }
                    $cols = array(array(), array());
                    $data['contents'][] = array('type' => 'empty', 'line' => $content);
                    $state = 'dump';
                }
            }
        }

        /* Just flush any remaining entries in the columns stack. */
        switch ($state) {
        case 'add':
            $data['contents'][] = array('type' => 'add', 'lines' => $cols[0]);
            break;

        case 'remove':
            /* We have some removal lines pending in our stack, so
             * flush them. */
            $data['contents'][] = array('type' => 'remove', 'lines' => $cols[0]);
            break;

        case 'change':
            /* We have both remove and addition lines, so this is a
             * change block. */
            $data['contents'][] = array('type' => 'change', 'old' => $cols[0], 'new' => $cols[1]);
            break;
        }

        if (isset($data)) {
            $ret[] = $data;
        }

        return $ret;
    }

    /**
     * Return the list of available diff types.
     *
     * @return array  The list of available diff types for use with get().
     */
    public function availableDiffTypes()
    {
        return $this->_diffTypes;
    }

    /**
     * Returns the location of the specified binary.
     *
     * @param string $binary  An external program name.
     *
     * @return boolean|string  The location of the external program or false
     *                         if it wasn't specified.
     */
    public function getPath($binary)
    {
        if (isset($this->_paths[$binary])) {
            return $this->_paths[$binary];
        }

        return false;
    }

    /**
     * Parse the users file, if present in the sourceroot, and return
     * a hash containing the requisite information, keyed on the
     * username, and with the 'desc', 'name', and 'mail' values inside.
     *
     * @return array  User data.
     * @throws Horde_Vcs_Exception
     */
    public function getUsers($usersfile)
    {
        /* Check that we haven't already parsed users. */
        if (isset($this->_users[$usersfile])) {
            return $this->_users[$usersfile];
        }

        if (!@is_file($usersfile) ||
            !($fl = @fopen($usersfile, VC_WINDOWS ? 'rb' : 'r'))) {
            throw new Horde_Vcs_Exception('Invalid users file: ' . $usersfile);
        }

        /* Discard the first line, since it'll be the header info. */
        fgets($fl, 4096);

        /* Parse the rest of the lines into a hash, keyed on username. */
        $users = array();
        while ($line = fgets($fl, 4096)) {
            if (!preg_match('/^\s*$/', $line) &&
                preg_match('/^(\w+)\s+(.+)\s+([\w\.\-\_]+@[\w\.\-\_]+)\s+(.*)$/', $line, $regs)) {
                $users[$regs[1]] = array(
                    'name' => trim($regs[2]),
                    'mail' => trim($regs[3]),
                    'desc' => trim($regs[4])
                );
            }
        }

        $this->_users[$usersfile] = $users;

        return $users;
    }

    /**
     * TODO
     *
     * $opts:
     * 'quicklog' - (boolean)
     * 'rev' - (string)
     * 'showAttic' - (boolean)
     */
    public function getDirObject($where, $opts = array())
    {
        $class = 'Horde_Vcs_Directory_' . $this->_driver;
        return new $class($this, $where, $opts);
    }

    /**
     * Function which returns a file pointing to the head of the requested
     * revision of a file.
     *
     * @param string $fullname  Fully qualified pathname of the desired file
     *                          to checkout.
     * @param string $rev       Revision number to check out.
     *
     * @return resource  A stream pointer to the head of the checkout.
     */
    public function checkout($fullname, $rev)
    {
        return null;
    }

    /**
     * TODO
     *
     * $opts:
     * 'quicklog' - (boolean)
     * 'branch' - (string)
     */
    public function getFileObject($filename, $opts = array())
    {
        $class = 'Horde_Vcs_File_' . $this->_driver;

        ksort($opts);
        $cacheId = implode('|', array($class, $this->sourceroot(), $filename, serialize($opts), $this->_cacheVersion));
        $fetchedFromCache = false;

        if (!empty($this->_cache)) {
            // TODO: Can't use filemtime() - Git bare repos contain no files
            if (file_exists($filename)) {
                $ctime = time() - filemtime($filename);
            } else {
                $ctime = 60;
            }
            if ($this->_cache->exists($cacheId, $ctime)) {
                $ob = unserialize($this->_cache->get($cacheId, $ctime));
                $fetchedFromCache = true;
            }
        }

        if (empty($ob) || !$ob) {
            $ob = new $class($filename, $opts);

        }
        $ob->setRepository($this);
        $ob->applySort(self::SORT_AGE);

        if (!empty($this->_cache) && !$fetchedFromCache) {
            $this->_cache->set($cacheId, serialize($ob));
        }

        return $ob;
    }

    /**
     * @param Horde_Vcs_File $fl  The file obejct
     * @param string $rev         The revision identifier
     */
    public function getLogObject($fl, $rev)
    {
        $class = 'Horde_Vcs_Log_' . $this->_driver;

        if (!is_null($rev) && !empty($this->_cache)) {
            $cacheId = implode('|', array($class, $this->sourceroot(), $fl->queryPath(), $rev, $this->_cacheVersion));

            // Individual revisions can be cached forever
            if ($this->_cache->exists($cacheId, 0)) {
                $ob = unserialize($this->_cache->get($cacheId, 0));
            }
        }

        if (empty($ob) || !$ob) {
            $ob = new $class($rev);

        }
        $ob->setRepository($this);
        $ob->setFile($fl);

        if (!is_null($rev) && !empty($this->_cache)) {
            $this->_cache->set($cacheId, serialize($ob));
        }

        return $ob;
    }

    /**
     * TODO
     *
     * @param array $opts  Options:
     * <pre>
     * 'file' - (string) TODO
     * 'range' - (array) TODO
     * </pre>
     *
     * @return Horde_Vcs_Patchset  Patchset object.
     */
    public function getPatchsetObject($opts = array())
    {
        $class = 'Horde_Vcs_Patchset_' . $this->_driver;

        ksort($opts);
        $cacheId = implode('|', array($class, $this->sourceroot(), serialize($opts), $this->_cacheVersion));

        if (!empty($this->_cache)) {
            if (isset($opts['file']) && file_exists($opts['file'])) {
                $ctime = time() - filemtime($opts['file']);
            } else {
                $ctime = 60;
            }

            if ($this->_cache->exists($cacheId, $ctime)) {
                return unserialize($this->_cache->get($cacheId, $ctime));
            }
        }

        $ob = new $class($this, $opts);

        if (!empty($this->_cache)) {
            $this->_cache->set($cacheId, serialize($ob));
        }

        return $ob;
    }

    /**
     * TODO
     */
    public function annotate($fileob, $rev)
    {
        return array();
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
        return $rev;
    }

    /**
     * @TODO ?
     */
    public function getDefaultBranch()
    {
        return 'HEAD';
    }

}

/**
 * Horde_Vcs_Cvs directory class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Directory
{
    /**
     * @var Horde_Vcs
     */
    protected $_rep;

    /**
     * @var string
     */
    protected $_dirName;

    /**
     * @var array
     */
    protected $_files = array();

    /**
     * @var array
     */
    protected $_atticFiles = array();

    /**
     * @var array
     */
    protected $_mergedFiles = array();

    /**
     * @var string
     */
    protected $_dirs = array();

    /**
     * @var string
     */
    protected $_moduleName;

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
        $this->_rep = $rep;
        $this->_moduleName = $dn;
        $this->_dirName = '/' . $dn;
    }

    /**
     * Return fully qualified pathname to this directory with no trailing /.
     *
     * @return string Pathname of this directory.
     */
    public function queryDir()
    {
        return $this->_dirName;
    }

    /**
     * TODO
     */
    public function queryDirList()
    {
        return $this->_dirs;
    }

    /**
     * TODO
     */
    public function queryFileList($showattic = false)
    {
        return ($showattic && isset($this->_mergedFiles))
            ? $this->_mergedFiles
            : $this->_files;
    }

    /**
     * Sort the contents of the directory in a given fashion and
     * order.
     *
     * @param integer $how  Of the form Horde_Vcs::SORT_[*] where * can be:
     *                      NONE, NAME, AGE, REV for sorting by name, age or
     *                      revision.
     * @param integer $dir  Of the form Horde_Vcs::SORT_[*] where * can be:
     *                      ASCENDING, DESCENDING for the order of the sort.
     */
    public function applySort($how = Horde_Vcs::SORT_NONE,
                              $dir = Horde_Vcs::SORT_ASCENDING)
    {
        // Always sort directories by name.
        natcasesort($this->_dirs);

        $this->_doFileSort($this->_files, $how);

        if (isset($this->_atticFiles)) {
            $this->_doFileSort($this->_atticFiles, $how);
        }

        if (isset($this->_mergedFiles)) {
            $this->_doFileSort($this->_mergedFiles, $how);
        }

        if ($dir == Horde_Vcs::SORT_DESCENDING) {
            $this->_dirs = array_reverse($this->_dirs);
            $this->_files = array_reverse($this->_files);
            if (isset($this->_mergedFiles)) {
                $this->_mergedFiles = array_reverse($this->_mergedFiles);
            }
        }
    }

    /**
     * TODO
     */
    protected function _doFileSort(&$fileList, $how = Horde_Vcs::SORT_NONE)
    {
        switch ($how) {
        case Horde_Vcs::SORT_AGE:
            usort($fileList, array($this, '_fileAgeSort'));
            break;

        case Horde_Vcs::SORT_NAME:
            usort($fileList, array($this, '_fileNameSort'));
            break;

        case Horde_Vcs::SORT_AUTHOR:
            usort($fileList, array($this, '_fileAuthorSort'));
            break;

        case Horde_Vcs::SORT_REV:
            usort($fileList, array($this, '_fileRevSort'));
            break;

        case Horde_Vcs::SORT_NONE:
        default:
            break;
        }
    }

    /**
     * Sort function for ascending age.
     */
    public function _fileAgeSort($a, $b)
    {
        $aa = $a->queryLastLog();
        $bb = $b->queryLastLog();
        return ($aa->queryDate() == $bb->queryDate())
            ? 0
            : (($aa->queryDate() < $bb->queryDate()) ? 1 : -1);
    }

    /**
     * Sort function by author name.
     */
    public function _fileAuthorSort($a, $b)
    {
        $aa = $a->queryLastLog();
        $bb = $b->queryLastLog();
        return ($aa->queryAuthor() == $bb->queryAuthor())
            ? 0
            : (($aa->queryAuthor() > $bb->queryAuthor()) ? 1 : -1);
    }

    /**
     * Sort function for ascending filename.
     */
    public function _fileNameSort($a, $b)
    {
        return strcasecmp($a->queryName(), $b->queryName());
    }

    /**
     * Sort function for ascending revision.
     */
    public function _fileRevSort($a, $b)
    {
        return $this->_rep->cmp($a->queryRevision(), $b->queryRevision());
    }

    /**
     * TODO
     */
    public function getBranches()
    {
        return array();
    }
}

/**
 * Horde_Vcs file class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_File
{
    /**
     * TODO
     */
    protected $_dir;

    /**
     * TODO
     */
    protected $_name;

    /**
     * TODO
     */
    public $logs = array();

    /**
     * TODO
     */
    protected $_revs = array();

    /**
     * TODO
     */
    protected $_rep;

    /**
     * TODO
     */
    protected $_quicklog;

    /**
     * TODO
     */
    protected $_branch = null;

    /**
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param string $filename  Full path to this file.
     * @param array  $opts      TODO
     */
    public function __construct($filename, $opts = array())
    {
        $this->_name = basename($filename);
        $this->_dir = dirname($filename);

        $this->_quicklog = !empty($opts['quicklog']);
        if (!empty($opts['branch'])) {
            $this->_branch = $opts['branch'];
        }
    }

    protected function _ensureRevisionsInitialized()
    {
    }

    protected function _ensureLogsInitialized()
    {
    }

    /**
     * When serializing, don't return the repository object
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('_rep'));
    }

    /**
     * TODO
     */
    public function setRepository($rep)
    {
        $this->_rep = $rep;
    }

    /**
     * TODO - better name, wrap an object around this?
     */
    public function getBlob($revision)
    {
        return $this->_rep->checkout($this->queryPath(), $revision);
    }

    /**
     * Has the file been deleted?
     *
     * @return boolean  Is this file deleted?
     */
    public function isDeleted()
    {
        return false;
    }

    /**
     * Returns name of the current file without the repository extensions.
     *
     * @return string  Filename without repository extension
     */
    function queryName()
    {
        return $this->_name;
    }

    /**
     * Returns the name of the current file as in the repository.
     *
     * @return string  Filename (without the path).
     */
    public function queryRepositoryName()
    {
        return $this->_name;
    }

    /**
     * Return the last revision of the current file on the HEAD branch.
     *
     * @return string  Last revision of the current file.
     * @throws Horde_Vcs_Exception
     */
    public function queryRevision()
    {
        $this->_ensureRevisionsInitialized();
        if (!isset($this->_revs[0])) {
            throw new Horde_Vcs_Exception('No revisions');
        }
        return $this->_revs[0];
    }

    /**
     * TODO
     */
    public function queryPreviousRevision($rev)
    {
        $this->_ensureRevisionsInitialized();
        $key = array_search($rev, $this->_revs);
        return (($key !== false) && isset($this->_revs[$key + 1]))
            ? $this->_revs[$key + 1]
            : null;
    }

   /**
     * Return the last Horde_Vcs_Log object in the file.
     *
     * @return Horde_Vcs_Log  Log object of the last entry in the file.
     * @throws Horde_Vcs_Exception
     */
    public function queryLastLog()
    {
        $this->_ensureRevisionsInitialized();
        $this->_ensureLogsInitialized();
        if (!isset($this->_revs[0]) || !isset($this->logs[$this->_revs[0]])) {
            throw new Horde_Vcs_Exception('No revisions');
        }
        return $this->logs[$this->_revs[0]];
    }

    /**
     * Sort the list of Horde_Vcs_Log objects that this file contains.
     *
     * @param integer $how  Horde_Vcs::SORT_REV (sort by revision),
     *                      Horde_Vcs::SORT_NAME (sort by author name), or
     *                      Horde_Vcs::SORT_AGE (sort by commit date).
     */
    public function applySort($how = Horde_Vcs::SORT_REV)
    {
        $this->_ensureLogsInitialized();

        switch ($how) {
        case Horde_Vcs::SORT_NAME:
            $func = 'Name';
            break;

        case Horde_Vcs::SORT_AGE:
            $func = 'Age';
            break;

        case Horde_Vcs::SORT_REV:
        default:
            $func = 'Revision';
            break;
        }

        uasort($this->logs, array($this, 'sortBy' . $func));
        return true;
    }

    /**
     * The sortBy*() functions are internally used by applySort.
     */
    public function sortByRevision($a, $b)
    {
        return $this->_rep->cmp($b->queryRevision(), $a->queryRevision());
    }

    public function sortByAge($a, $b)
    {
        return $b->queryDate() - $a->queryDate();
    }

    public function sortByName($a, $b)
    {
        return strcmp($a->queryAuthor(), $b->queryAuthor());
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return string  Fully qualified filename of this object.
     */
    public function queryFullPath()
    {
        return $this->_rep->sourceroot() . '/' . $this->queryModulePath();
    }

    /**
     * Return the filename relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return $this->_dir . '/' . $this->_name;
    }

    /**
     * Return the "base" filename (i.e. the filename needed by the various
     * command line utilities).
     *
     * @return string  A filename.
     */
    public function queryPath()
    {
        return $this->queryFullPath();
    }

    /**
     * TODO
     */
    public function queryBranches()
    {
        return array();
    }

    /**
     * TODO
     */
    public function queryLogs($rev = null)
    {
        $this->_ensureLogsInitialized();
        return is_null($rev)
            ? $this->logs
            : (isset($this->logs[$rev]) ? $this->logs[$rev] : null);
    }

    /**
     * TODO
     */
    public function revisionCount()
    {
        $this->_ensureRevisionsInitialized();
        return count($this->_revs);
    }

    /**
     * TODO
     */
    public function querySymbolicRevisions()
    {
        return array();
    }
}

/**
 * Horde_Vcs log class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Log
{
    protected $_rep;
    protected $_file;
    protected $_files = array();
    protected $_rev;
    protected $_author;
    protected $_tags = array();
    protected $_date;
    protected $_log;
    protected $_state;
    protected $_lines = '';
    protected $_branches = array();

    /**
     * Constructor.
     */
    public function __construct($rev)
    {
        $this->_rev = $rev;
    }

    protected function _ensureInitialized()
    {
    }

    /**
     * When serializing, don't return the repository object
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('_file', '_rep'));
    }

    /**
     * TODO
     */
    public function setRepository($rep)
    {
        $this->_rep = $rep;
    }

    public function setFile(Horde_Vcs_File $file)
    {
        $this->_file = $file;
    }

    /**
     * TODO
     */
    public function queryRevision()
    {
        $this->_ensureInitialized();
        return $this->_rev;
    }

    /**
     * TODO
     */
    public function queryDate()
    {
        $this->_ensureInitialized();
        return $this->_date;
    }

    /**
     * TODO
     */
    public function queryAuthor()
    {
        $this->_ensureInitialized();
        return $this->_author;
    }

    /**
     * TODO
     */
    public function queryLog()
    {
        $this->_ensureInitialized();
        return $this->_log;
    }

    /**
     * TODO
     */
    public function queryBranch()
    {
        $this->_ensureInitialized();
        return array();
    }

    /**
     * TODO
     */
    public function queryChangedLines()
    {
        $this->_ensureInitialized();
        return $this->_lines;
    }

    /**
     * TODO
     */
    public function queryTags()
    {
        $this->_ensureInitialized();
        return $this->_tags;
    }

    /**
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return array  Hash of symbolic names => branch numbers.
     */
    public function querySymbolicBranches()
    {
        $this->_ensureInitialized();

        $symBranches = array();
        $branches = $this->_file->queryBranches();

        foreach ($this->_branches as $branch) {
            if (($key = array_search($branch, $branches)) !== false) {
                $symBranches[$key] = $branch;
            }
        }

        return $symBranches;
    }

    /**
     * TODO
     */
    public function queryFiles($file = null)
    {
        $this->_ensureInitialized();
        return is_null($file)
            ? $this->_files
            : (isset($this->_files[$file]) ? $this->_files[$file] : array());
    }
}

/**
 * Horde_Vcs patchset class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Patchset
{
    const MODIFIED = 0;
    const ADDED = 1;
    const DELETED = 2;

    /**
     * @var array
     */
    protected $_patchsets = array();

    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param string $file    The filename to create patchsets for.
     * @param array $opts     Additional options:
     * <pre>
     * 'range' - (array) The patchsets to process.
     *           DEFAULT: None (all patchsets are processed).
     * </pre>
     */
    abstract public function __construct($rep, $file, $opts = array());

    /**
     * TODO
     *
     * @return array  TODO
     * 'date'
     * 'author'
     * 'branches'
     * 'tags'
     * 'log'
     * 'members' - array:
     *     'file'
     *     'from'
     *     'to'
     *     'status'
     */
    public function getPatchsets()
    {
        return $this->_patchsets;
    }
}
