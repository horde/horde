<?php
/**
 * Horde_Vcs file class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
