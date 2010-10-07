<?php
/**
 * Luxor storage implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/drivers/luxor.sql
 * script.
 *
 * $Horde: luxor/lib/Driver/sql.php,v 1.29 2007/09/23 13:32:35 jan Exp $
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Driver_sql extends Luxor_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * The id of the source that we are dealing with.
     *
     * @var string
     */
    var $_source;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Symbol cache.
     *
     * @var array
     */
    var $_symcache = array();

    /**
     * Description ID cache.
     *
     * @var array
     */
    var $_decIdcache = array();

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $source  The name of the source.
     * @param array  $params  A hash containing connection parameters.
     */
    function Luxor_Driver_sql($source, $params = array())
    {
        $this->_source = $source;
        $this->_params = $params;
    }

    /**
     * Adds a symbol definition to the sybmol index.
     *
     * @param string $symname  The symbol's name.
     * @param integer $fileId  The unique ID of the file where this symbol was
     *                         defined.
     * @param integer $line    The linenumber where this symbol was defined.
     * @param integer $langid  The unique ID of the language the file was
     *                         written in.
     * @param integer $type    The symbol type.
     *
     * @return mixed           PEAR_Error on error, true on success.
     */
    function index($symname, $fileId, $line, $langid, $type)
    {
        $this->_connect();

        $symid = $this->symid($symname);
        if (is_a($symid, 'PEAR_Error')) {
            return $symid;
        }

        /* I have no idea what this is about yet
        if ($relsym) {
            $relsym = $this->symid($relsym);
            if (is_a($relsym, 'PEAR_Error')) {
                return $relsym;
            }
            $relsym = $this->_db->quote($relsym);
        } else {
            $relsym = 'NULL';
        }
        */

        $query = 'INSERT INTO luxor_indexes (symid, fileid, line, declid)' .
                 ' VALUES (?, ?, ?, ?)';
        $values = array($symid, $fileId, $line, $this->getDecId($langid, $type));
        return $this->_db->query($query, $values);
    }

    /**
     * Add a symbol reference to the reference index.
     *
     * @param string $symname  The name of the used symbol.
     * @param integer $fileId  The unique ID of the file in which the symbol
     *                         was used.
     * @param integer $line    The number of line in which the symbol was used.
     *
     * @return mixed           PEAR_Error on error, true on success.
     */
    function reference($symname, $fileId, $line)
    {
        $this->_connect();

        $result = $this->_db->query('INSERT INTO luxor_usage (fileid, line, symid) VALUES (?, ?, ?)',
                                    array($fileId, $line, $this->symid($symname)));
        return $result;
    }

    /**
     * Returns a unique ID for a given filename.
     *
     * @param string $filename  The name of the file.
     * @param string $tag       The tag of the file.
     *
     * @return integer  A unique ID for this file or PEAR_Error on error.
     */
    function fileId($filename, $tag = '')
    {
        static $files = array();

        /* Have we already been asked for this file? */
        if (isset($files[$filename])) {
            return $files[$filename];
        }

        $this->_connect();

        /* Has an ID already been created for this file? */
        $query = 'SELECT fileid FROM luxor_files' .
                 ' WHERE tag = ? AND source = ? AND filename = ?';
        $values = array($tag, $this->_source, $filename);

        $fileId = $this->_db->getOne($query, $values);
        if (empty($fileId) || is_a($fileId, 'PEAR_Error')) {
            return false;
        }
        $files[$filename] = $fileId;

        return $fileId;
    }

    /**
     * Created a unique ID for a given filename.
     *
     * @param string $filename      The name of the file.
     * @param string $tag           The tag of the file.
     * @param integer $lastmodified The timestamp the file was last modified.
     *
     * @return integer              A unique ID for this file or PEAR_Error on error.
     */
    function createFileId($filename, $tag = '', $lastmodified)
    {
        $this->_connect();

        $fileId = $this->_db->nextId('luxor_files');
        if (is_a($fileId, 'PEAR_Error')) {
            return $fileId;
        }

        /* Create an ID for this file. */
        $query = 'INSERT INTO luxor_files (fileid, filename, source, tag, lastmodified) VALUES (?, ?, ?, ?, ?)';
        $values = array((int)$fileId,
                        $filename,
                        $this->_source,
                        $tag,
                        $lastmodified);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $fileId;
    }

    /**
     * Returns a unique ID for a given symbol.
     *
     * @param string $symname  The name of the symbol.
     *
     * @return int             A unique ID for this symbol or PEAR_Error on error.
     */
    function symid($symname)
    {
        /* Do we have this symbol in the symbol cache yet? */
        if (!isset($this->_symcache[$symname])) {
            $this->_connect();
            /* Has an ID already been created for this symbol? */
            $query = 'SELECT symid FROM luxor_symbols' .
                     ' WHERE source = ? AND symname = ?';
            $values = array($this->_source, $symname);
            $symid = $this->_db->getOne($query, $values);
            if (is_null($symid) || is_a($symid, 'PEAR_Error')) {
                /* Create an ID for this symbol. */
                $symid = $this->_db->nextId('luxor_symbols');
                if (is_a($symid, 'PEAR_Error')) {
                    return $symid;
                }
                $result = $this->_db->query('INSERT INTO luxor_symbols (symid, symname, source) VALUES (?, ?, ?)',
                                            array((int)$symid, $symname, $this->_source));
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
            $this->_symcache[$symname] = $symid;
        }

        return $this->_symcache[$symname];
    }

    /**
     * Returns the name of a symbol from its unique ID.
     *
     * @param integer $symid  The ID of the symbol
     *
     * @return string  The name of the symbol or PEAR_Error on error
     */
    function symname($symid)
    {
        /* Don't we have this symbol in the symbol cache yet? */
        $this->_connect();

        if (in_array($symid, $this->_symcache)) {
            return array_key($symid, $this->_symcache);
        }

        $query = 'SELECT symname FROM luxor_symbols WHERE symid = ?';
        $values = array($symid);
        $symname = $this->_db->getOne($query, $values);
        $this->_symcache[$symname] = $symid;

        return $symname;
    }

    /**
     * Checks if the given name is a known symbol.
     *
     * @param string $symname  The potential symbol name.
     *
     * @return integer  The symbol's id or null if it wasn't a symbol.
     */
    function isSymbol($symname, $altsources = array())
    {
        if (!isset($this->_symcache[$symname])) {
            $this->_connect();

            $altsql = '';
            $altvalues = array();
            if (!is_array($altsources)) {
                $altsources = array($altsources);
            }
            foreach ($altsources as $source) {
                $altsql .= ' OR source = ?';
                $altvalues[] = $source;
            }

            array_unshift($altvalues, $this->_source);
            $values = $altvalues;
            $values[] = $symname;

            $symid = $this->_db->getOne('SELECT symid FROM luxor_symbols' .
                                        ' WHERE (source = ?' . $altsql . ')' .
                                        ' AND symname = ?',
                                        $values);
            $this->_symcache[$symname] = $symid;
        }

        return $this->_symcache[$symname];
    }

    /**
     * If this file has not been indexed earlier, mark it as being
     * indexed now.
     *
     * @param integer $fileId  The file's unique ID.
     *
     * @return boolean  True if the file has been marked as being indexed,
     *                  false if it was already indexed.
     */
    function toIndex($fileId)
    {
        $this->_connect();

        $status = $this->_db->getOne('SELECT status FROM luxor_status' .
                                     ' WHERE fileid = ?',
                                     array($fileId));
        if (empty($status)) {
            $this->_db->query('INSERT INTO luxor_status (fileid, status)' .
                              ' VALUES (?, 0)',
                              array($fileId + 0));
        }
        $query = 'UPDATE luxor_status SET status = 1' .
                 ' WHERE fileid = ? AND status <= 0';
        $values = array($fileId);
        return $this->_db->query($query, $values);
    }

    /**
     * If this file has not been referenced earlier, mark it as being
     * referenced now.
     *
     * @param integer $fileId  The file's unique ID.
     *
     * @return boolean  True if the file has been marked as being referenced,
     *                  false if it was already referenced.
     */
    function toReference($fileId)
    {
        $this->_connect();

        $query = 'UPDATE luxor_status SET status = 2' .
                 ' WHERE fileid = ? AND status <= 1';
        $values = array($fileId);
        return $this->_db->query($query, $values);
    }

    /**
     * Return the last time the entry for a file was modified.
     *
     * @param string $filename  The filename to check.
     *
     * @return integer  The last modified time, or 0 if there is an error.
     */
    function getLastModified($filename)
    {
        static $lastModified;

        if (isset($lastModified[$filename])) {
            return $lastModified[$filename];
        }

        $this->_connect();
        $query = 'SELECT lastmodified FROM luxor_files' .
                 ' WHERE source = ? AND filename = ?';
        $values = array($this->_source, $filename);
        $res = $this->_db->getOne($query, $values);
        $lastModified[$filename] = is_a($res, 'PEAR_Error') ? 0 : $res;

        return $lastModified[$filename];
    }

    /**
     * Empties the current symbol cache.
     *
     * This function should be called before parsing each new file.
     * If this is not done too much memory will be used and things
     * will become very slow.
     */
    function clearCache()
    {
        $this->_symcache = array();
    }

    /**
     * Cleans the database for a fresh import of data.
     *
     * This function should be called before parsing the source tree
     * again, to avoid duplicate entries in the database.
     */
    function clearIndex()
    {
        $this->_connect();

        $this->_db->query('DELETE FROM luxor_declarations');
        $this->_db->query('DELETE FROM luxor_files');
        $this->_db->query('DELETE FROM luxor_indexes');
        $this->_db->query('DELETE FROM luxor_status');
        $this->_db->query('DELETE FROM luxor_symbols');
        $this->_db->query('DELETE FROM luxor_usage');
    }

    /**
     * Returns an unique ID for a description of a symbol type.
     *
     * @param integer $lang   The language's unique ID.
     * @param string $string  The symbol type description.
     *
     * return mixed  A unique ID for this description or PEAR_Error on error.
     */
    function getDecId($lang, $string)
    {
        $this->_connect();

        if (!isset($this->_decIdcache[$lang])) {
            $this->_decIdcache[$lang] = array();
        }

        if (!isset($this->_decIdcache[$lang][$string])) {
            $query = 'SELECT declid FROM luxor_declarations' .
                     ' WHERE langid = ? AND declaration = ?';
            $values = array($lang, $string);
            $decId = $this->_db->getOne($query, $values);
            if (is_null($decId) || is_a($decId, 'PEAR_Error')) {
                /* Create an ID for this declaration. */
                $decId = $this->_db->nextId('luxor_declarations');
                if (is_a($decId, 'PEAR_Error')) {
                    return $decId;
                }
                $this->_db->query('INSERT INTO luxor_declarations (declid, langid, declaration)' .
                                  ' VALUES (?, ?, ?)',
                                  array((int)$decId, $lang, $string));
            }
            $this->_decIdcache[$lang][$string] = $decId;
        }

        return $this->_decIdcache[$lang][$string];
    }

    /**
     * Locate the definitions of a symbol.
     *
     * @param integer $symid  The symbol id.
     * @param string $tag     The tag of the file.
     *
     * @return array  Nested hash with elements 'filename', 'line', and
     *                'declaration'.
     */
    function getIndex($symid, $tag = '')
    {
        $this->_connect();
        $query = 'SELECT filename, line, declaration FROM ' .
                 'luxor_files, luxor_indexes, luxor_declarations WHERE ' .
                 'luxor_files.fileid = luxor_indexes.fileid AND ' . // join files to indexes
                 'luxor_indexes.declid = luxor_declarations.declid AND ' . // join indexes to declarations
                 'luxor_indexes.symid = ? AND ' .
                 'luxor_files.tag = ? AND ' .
                 'luxor_files.source = ?';
        $values = array((int)$symid, $tag, $this->_source);

        return $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Locate the usage of a symbol.
     *
     * @param integer $symid  The symbol id.
     * @param string $tag     The tag of the file.
     *
     * @return array  Nested hash with elements 'filename', and 'line'.
     */
    function getReference($symid, $tag = '')
    {
        $this->_connect();
        $query = 'SELECT filename, line FROM ' .
                 'luxor_usage, luxor_files WHERE ' .
                 'luxor_usage.fileid = luxor_files.fileid AND ' .
                 'luxor_usage.symid = ? AND ' .
                 'luxor_files.tag = ? AND ' .
                 'luxor_files.source = ?';
        $values = array((int)$symid, $tag, $this->_source);

        return $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Search for symbols matching $symbol.
     *
     * @param string $symbol  The symbol name to search for.
     *
     * @return array  Any symids matching $symbol.
     */
    function searchSymbols($symbol)
    {
        $this->_connect();
        $query = 'SELECT symid, symid FROM luxor_symbols WHERE symname LIKE ?';
        $values = array($symbol . '%');

        return $this->_db->getAssoc($query, false, $values);
    }

    /**
     * Get source that a symbol is from.
     *
     * @param $symid  The symbol id.
     *
     * @return string  The source id.
     */
    function getSourceBySymbol($symid)
    {
        $this->_connect();

        return $this->_db->getOne('SELECT source FROM luxor_symbols' .
                                  ' WHERE symid = ?',
                                  array($symid));
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     */
    function _connect()
    {
        if (!$this->_connected) {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'luxor', 'storage');
            $this->_connected = true;
        }

        return true;
    }

}
