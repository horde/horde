<?php
/* Need to define this outside of class since constants in class can not be
 * assigned from a function return. */
define('VC_WINDOWS', !strncasecmp(PHP_OS, 'WIN', 3));

/**
 * Version Control generalized library.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
    protected $_users;

    /**
     * The current driver.
     *
     * @var string
     */
    protected $_driver;

    /**
     * Cached objects.
     *
     * @var array
     */
    protected $_cached = array();

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
     * Attempts to return a concrete Horde_Vcs instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Vcs subclass to return.
     *                       The code is dynamically included.
     * @param array $params  A hash containing any additional configuration
     *                       or  parameters a subclass might need.
     *
     * @return Horde_Vcs  The newly created concrete instance, or PEAR_Error on
     *                   failure.
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Horde_Vcs_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        return PEAR::raiseError($class . ' not found.');
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $pos = strrpos(get_class($this), '_');
        $this->_driver = substr(get_class($this), $pos + 1);
    }

    /**
     * Does this driver support the given feature
     *
     * @return boolean  True if driver supports the given feature.
     */
    public function supportsFeature($feature)
    {
        switch ($feature) {
        case 'branches':
            return $this->_branches;

        case 'deleted':
            return $this->_deleted;

        case 'patchsets':
            return $this->_patchsets;

        default:
            return false;
        }
    }

    /**
     * Return the source root for this repository, with no trailing /
     *
     * @return string  Source root for this repository.
     */
    public function sourceroot()
    {
        return $this->_sourceroot;
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
        $rev_ob = $this->getRevisionObject();
        return $rev_ob->valid($rev);
    }

    /**
     * TODO
     */
    public function isFile($where)
    {
        return true;
    }

    /**
     * Throw an exception if the revision number isn't valid.
     *
     * @param mixed $rev The revision number
     *
     * @return void
     * @throws Horde_Vcs_Exception
     */
    public function assertValidRevision($rev)
    {
        if (!$this->isValidRevision($rev)) {
            throw new Horde_Vcs_Exception('Invalid revision number');
        }
    }

    /**
     * Create a range of revisions between two revision numbers.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $r1           The initial revision.
     * @param string $r2           The ending revision.
     *
     * @return array  The revision range, or empty if there is no straight
     *                line path between the revisions.
     */
    public function getRevisionRange($file, $r1, $r2)
    {
        if (!isset($this->_cache['diff'])) {
            $class = 'Horde_Vcs_Diff_' . $this->_driver;
            $this->_cache['diff'] = new $class();
        }
        return $this->_cache['diff']->getRevisionRange($this, $file, $r1, $r2);
    }

    /**
     * Returns the location of the specified binary.
     *
     * @param string $binary  An external program name.
     *
     * @return boolean|string  The location of the external program or false if
     *                         it wasn't specified.
     */
    public function getPath($binary)
    {
        if (isset($this->_paths[$binary])) {
            return $this->_paths[$binary];
        }

        return false;
    }

    /**
     * Parse the users file, if present in the source root, and return
     * a hash containing the requisite information, keyed on the
     * username, and with the 'desc', 'name', and 'mail' values inside.
     *
     * @return boolean|array  False if the file is not present, otherwise
     *                        $this->_users populated with the data
     */
    public function getUsers($usersfile)
    {
        /* Check that we haven't already parsed users. */
        if (isset($this->_users) && is_array($this->_users)) {
            return $this->_users;
        }

        if (!@is_file($usersfile) || !($fl = @fopen($usersfile, VC_WINDOWS ? 'rb' : 'r'))) {
            return false;
        }

        $this->_users = array();

        /* Discard the first line, since it'll be the header info. */
        fgets($fl, 4096);

        /* Parse the rest of the lines into a hash, keyed on
         * username. */
        while ($line = fgets($fl, 4096)) {
            if (preg_match('/^\s*$/', $line)) {
                continue;
            }
            if (!preg_match('/^(\w+)\s+(.+)\s+([\w\.\-\_]+@[\w\.\-\_]+)\s+(.*)$/', $line, $regs)) {
                continue;
            }

            $this->_users[$regs[1]]['name'] = trim($regs[2]);
            $this->_users[$regs[1]]['mail'] = trim($regs[3]);
            $this->_users[$regs[1]]['desc'] = trim($regs[4]);
        }

        return $this->_users;
    }

    public function queryDir($where)
    {
        $class = 'Horde_Vcs_Directory_' . $this->_driver;
        return new $class($this, $where);
    }

    public function getCheckout($file, $rev)
    {
        if (!isset($this->_cache['co'])) {
            $class = 'Horde_Vcs_Checkout_' . $this->_driver;
            $this->_cache['co'] = new $class();
        }
        return $this->_cache['co']->get($this, $file->queryFullPath(), $rev);
    }

    public function getDiff($file, $rev1, $rev2, $type = 'unified', $num = 3,
                            $ws = true)
    {
        if (!isset($this->_cache['diff'])) {
            $class = 'Horde_Vcs_Diff_' . $this->_driver;
            $this->_cache['diff'] = new $class();
        }
        return $this->_cache['diff']->get($this, $file, $rev1, $rev2, $type, $num, $ws);
    }

    public function availableDiffTypes()
    {
        if (!isset($this->_cache['diff'])) {
            $class = 'Horde_Vcs_Diff_' . $this->_driver;
            $this->_cache['diff'] = new $class();
        }
        return $this->_cache['diff']->availableDiffTypes();
    }

    public function getFileObject($filename, $cache = null, $quicklog = false)
    {
        $class = 'Horde_Vcs_File_' . $this->_driver;
        $vc_file = new $class($this, $filename, $cache, $quicklog);
        return $vc_file->getFileObject();
    }

    public function getAnnotateObject($filename)
    {
        $class = 'Horde_Vcs_Annotate_' . $this->_driver;
        return new $class($this, $filename);
    }

    public function getPatchsetObject($filename, $cache = null)
    {
        $class = 'Horde_Vcs_Patchset_' . $this->_driver;
        $vc_patchset = new $class($this, $filename, $cache);
        return $vc_patchset->getPatchsetObject();
    }

    public function getRevisionObject()
    {
        if (!isset($this->_cache['rev'])) {
            $class = 'Horde_Vcs_Revision_' . $this->_driver;
            $this->_cache['rev'] = new $class();
        }
        return $this->_cache['rev'];
    }
}

/**
 * Horde_Vcs annotate class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Annotate
{
    protected $_file;
    protected $_rep;

    /**
     * Constructor
     *
     * TODO
     */
    public function __construct($rep, $file)
    {
        $this->_rep = $rep;
        $this->_file = $file;
    }

    /**
     * TODO
     */
    abstract public function doAnnotate($rev);
}

/**
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Checkout
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
    abstract function get($rep, $fullname, $rev);
}

/**
 * @package Horde_Vcs
 */
class Horde_Vcs_Diff
{
    /**
     * The available diff types.
     *
     * @var array
     */
    protected $_diffTypes = array('column', 'context', 'ed', 'unified');

    /**
     * Obtain a tree containing information about the changes between
     * two revisions.
     *
     * @param array $raw  An array of lines of the raw unified diff,
     *                    normally obtained through Horde_Vcs_Diff::get().
     *
     * @return array  @TODO
     */
    public function humanReadable($raw)
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
        $rev_ob = $rep->getRevisionObject();

        if ($rev_ob->cmp($r1, $r2) == 1) {
            $curr = $rev_ob->prev($r1);
            $stop = $rev_ob->prev($r2);
            $flip = true;
        } else {
            $curr = $r2;
            $stop = $r1;
            $flip = false;
        }

        $ret_array = array();

        do {
            $ret_array[] = $curr;
            $curr = $rev_ob->prev($curr);
            if ($curr == $stop) {
                return ($flip) ? array_reverse($ret_array) : $ret_array;
            }
        } while ($rev_ob->cmp($curr, $stop) != -1);

        return array();
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
}

/**
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Directory
{
    protected $_rep;
    protected $_dirName;
    protected $_files;
    protected $_atticFiles;
    protected $_mergedFiles;
    protected $_dirs;
    protected $_parent;
    protected $_moduleName;

    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository
     *
     * @param Horde_Vcs $rp            The Repository object this directory
     *                                is part of.
     * @param string $dn              Path to the directory.
     * @param Horde_Vcs_Directory $pn  The parent Directory object to this one.
     */
    public function __construct($rep, $dn, $pn = '')
    {
        $this->_rep = $rep;
        $this->_parent = $pn;
        $this->_moduleName = $dn;
        $this->_dirName = '/' . $dn;
        $this->_dirs = $this->_files = array();
    }

    /**
     * Return fully qualified pathname to this directory with no
     * trailing /.
     *
     * @return Pathname of this directory
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
     * TODO
     */
    abstract public function browseDir($cache = null, $quicklog = true,
                                       $showattic = false);

    /**
     * Sort the contents of the directory in a given fashion and
     * order.
     *
     * @param integer $how  Of the form SORT_* where * can be:
     *                      NONE, NAME, AGE, REV for sorting by name, age or
     *                      revision.
     * @param integer $dir  Of the form SORT_* where * can be:
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
            usort($fileList, array($this, 'fileAgeSort'));
            break;

        case Horde_Vcs::SORT_NAME:
            usort($fileList, array($this, 'fileNameSort'));
            break;

        case Horde_Vcs::SORT_AUTHOR:
            usort($fileList, array($this, 'fileAuthorSort'));
            break;

        case Horde_Vcs::SORT_REV:
            $this->_revob = $this->_rep->getRevisionObject();
            usort($fileList, array($this, 'fileRevSort'));
            break;

        case Horde_Vcs::SORT_NONE:
        default:
            break;
        }
    }
    /**
     * Sort function for ascending age.
     */
    public function fileAgeSort($a, $b)
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
    public function fileAuthorSort($a, $b)
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
    public function fileNameSort($a, $b)
    {
        return strcasecmp($a->name, $b->name);
    }

    /**
     * Sort function for ascending revision.
     */
    public function fileRevSort($a, $b)
    {
        return $this->_revob->cmp($a->queryHead(), $b->queryHead());
    }

}

/**
 * @package Horde_Vcs
 */
class Horde_Vcs_File
{
    public $rep;
    public $dir;
    public $name;
    public $logs;
    public $revs;
    public $head;
    public $quicklog;
    public $symrev;
    public $revsym;
    public $branches;
    public $revob;

    /**
     * TODO
     */
    public function setRepository($rep)
    {
        $this->rep = $rep;
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
     * Returns the name of the current file as in the repository
     *
     * @return string  Filename (without the path)
     */
    public function queryRepositoryName()
    {
        return $this->name;
    }

    /**
     * Return the last revision of the current file on the HEAD branch
     *
     * @return Last revision of the current file
     */
    public function queryRevision()
    {
        if (!isset($this->revs[0])) {
            return PEAR::raiseError('No revisions');
        }
        return $this->revs[0];
    }

    public function queryPreviousRevision($rev)
    {
        $last = false;
        foreach ($this->revs as $entry) {
            if ($last) {
                return $entry;
            }
            if ($entry == $rev) {
                $last = true;
            }
        }

        return false;
    }

    /**
     * Return the HEAD (most recent) revision number for this file.
     *
     * @return HEAD revision number
     */
    public function queryHead()
    {
        return $this->queryRevision();
    }

   /**
     * Return the last Horde_Vcs_Log object in the file.
     *
     * @return Horde_Vcs_Log of the last entry in the file
     */
    public function queryLastLog()
    {
        if (!isset($this->revs[0]) || !isset($this->logs[$this->revs[0]])) {
            return PEAR::raiseError('No revisions');
        }
        return $this->logs[$this->revs[0]];
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
        switch ($how) {
        case Horde_Vcs::SORT_NAME:
            $func = 'Name';
            break;

        case Horde_Vcs::SORT_AGE:
            $func = 'Age';
            break;

        case Horde_Vcs::SORT_REV:
        default:
            $this->revob = $this->rep->getRevisionObject();
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
        return $this->revob->cmp($b->rev, $a->rev);
    }

    public function sortByAge($a, $b)
    {
        return $b->date - $a->date;
    }

    public function sortByName($a, $b)
    {
        return strcmp($a->author, $b->author);
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return Fully qualified filename of this object
     */
    public function queryFullPath()
    {
        return $this->rep->sourceroot() . '/' . $this->queryModulePath();
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return $this->dir . '/' . $this->name;
    }

}

/**
 * Horde_Vcs log class.
 *
 * @package Horde_Vcs
 */
class Horde_Vcs_Log
{
    public $rep;
    public $file;
    public $tags;
    public $rev;
    public $date;
    public $log;
    public $author;
    public $state;
    public $lines;
    public $branches = array();

    /**
     * Constructor.
     */
    public function __construct($rep, $fl)
    {
        $this->rep = $rep;
        $this->file = $fl;
    }

    public function queryDate()
    {
        return $this->date;
    }

    public function queryRevision()
    {
        return $this->rev;
    }

    public function queryAuthor()
    {
        return $this->author;
    }

    public function queryLog()
    {
        return $this->log;
    }

    public function queryChangedLines()
    {
        return isset($this->lines) ? $this->lines : '';
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
 * Horde_Vcs patchset class.
 *
 * @package Horde_Vcs
 */
abstract class Horde_Vcs_Patchset
{
    protected $_rep;
    public $_patchsets = array();
    protected $_file;
    protected $_cache;
    protected $_ctime = 3600;

    /**
     * Create a patchset object.
     *
     * @param string $file  The filename to get patchsets for.
     */
    public function __construct($rep, $file, $cache = null)
    {
        $this->_rep = $rep;
        $this->_file = $file;
        $this->_cache = $cache;
    }

    public function &getPatchsetObject()
    {
        /* The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data. */
        if ($this->_cache) {
            $cacheVersion = 1;
            $cacheId = $this->_rep->sourceroot() . '_n' . $this->_file . '_f_v' . $cacheVersion;
        }

        if ($this->_cache &&
            $this->_cache->exists($cacheId, $this->_ctime)) {
            $psOb = unserialize($this->_cache->get($cacheId, $this->_ctime));
            $psOb->setRepository($this->_rep);
        } else {
            $class_name = get_class($this);
            $psOb = new $class_name($this->_rep, $this->_file, $this->_cache);
            $psOb->setRepository($this->_rep);
            if (is_a(($result = $psOb->getPatchsets()), 'PEAR_Error')) {
                return $result;
            }

            if ($this->_cache) {
                $this->_cache->set($cacheId, serialize($psOb));
            }
        }

        return $psOb;
    }

    abstract public function getPatchsets();

    public function setRepository($rep)
    {
        $this->_rep = $rep;
    }
}

/**
 * Horde_Vcs revisions class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_VC
 */
class Horde_Vcs_Revision
{
    /**
     * Validation function to ensure that a revision string is of the right
     * form.
     *
     * @param mixed $rev  The purported revision string.
     *
     * @return boolean  True if it is a valid revision string.
     */
    public function valid($rev)
    {
        return true;
    }

    /**
     * Given a revision string, remove a given number of portions from
     * it. For example, if we remove 2 portions of 1.2.3.4, we are
     * left with 1.2.
     *
     * @param string $val      Input revision string.
     * @param integer $amount  Number of portions to strip.
     *
     * @return string  Stripped revision string.
     */
    public function strip($val, $amount = 1)
    {
        return $val;
    }

    /**
     * The size of a revision number is the number of portions it has.
     * For example, 1,2.3.4 is of size 4.
     *
     * @param string $val  Revision number to determine size of
     *
     * @return integer  Size of revision number
     */
    public function sizeof($val)
    {
        return 1;
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
     * Return the logical revision before this one.
     *
     * @param string $rev  Revision string to decrement.
     *
     * @return string|boolean  Revision string, or false if none could be
     *                         determined.
     */
    public function prev($rev)
    {
        return false;
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

}
