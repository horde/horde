<?php
/**
 * Base file class.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @package Vcs
 */
abstract class Horde_Vcs_File_Base
{
    /**
     * The directory of this file.
     *
     * @var string
     */
    protected $_dir;

    /**
     * The name of this file.
     *
     * @var string
     */
    protected $_name;

    /**
     * TODO
     *
     * @var array
     */
    public $logs = array();

    /**
     * TODO
     *
     * @var array
     */
    protected $_revs = array();

    /**
     * TODO
     */
    protected $_rep;

    /**
     * TODO
     *
     * @var boolean
     */
    protected $_quicklog;

    /**
     * TODO
     *
     * @var string
     */
    protected $_branch = null;

    /**
     * Have we initalized logs and revisions?
     *
     * @var boolean
     */
    protected $_initialized = false;

    /**
     * Constructor.
     *
     * @param string $filename  Full path (inside the source root) to this file.
     * @param array $opts       Additional parameters:
     *                          - 'quicklog': (boolean)
     *                          - 'branch': (string)
     */
    public function __construct($filename, $opts = array())
    {
        $this->_name = basename($filename);
        $this->_dir = dirname($filename);
        if ($this->_dir == '.') {
            $this->_dir = '';
        }

        $this->_quicklog = !empty($opts['quicklog']);
        if (!empty($opts['branch'])) {
            $this->_branch = $opts['branch'];
        }
    }

    /**
     * When serializing, don't return the repository object
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('_rep'));
    }

    abstract protected function _init();

    protected function _ensureRevisionsInitialized()
    {
        if (!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
    }

    protected function _ensureLogsInitialized()
    {
        if (!$this->_initialized) {
            $this->_init();
            $this->_initialized = true;
        }
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
        return $this->_rep->checkout($this->getPath(), $revision);
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
    public function getFileName()
    {
        return $this->_name;
    }

    /**
     * Returns the last revision of the current file on the HEAD branch.
     *
     * @return string  Last revision of the current file.
     * @throws Horde_Vcs_Exception
     */
    public function getRevision()
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
    public function getPreviousRevision($rev)
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
    public function getLastLog()
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
        return $this->_rep->cmp($b->getRevision(), $a->getRevision());
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
     * Return the filename relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function getSourcerootPath()
    {
        return ltrim($this->_dir . '/' . $this->_name, '/');
    }

    /**
     * Return the "base" filename (i.e. the filename needed by the various
     * command line utilities).
     *
     * @return string  A filename.
     */
    public function getPath()
    {
        return $this->_rep->sourceroot . '/' . $this->getSourcerootPath();
    }

    /**
     * TODO
     */
    public function getBranches()
    {
        return array();
    }

    /**
     * TODO
     */
    public function getLogs($rev = null)
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
    public function getSymbolicRevisions()
    {
        return array();
    }
}
