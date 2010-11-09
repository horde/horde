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
