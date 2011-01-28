<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     James Pepin <james@jamespepin.com>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 */

/**
 * Class for parsing a stream into individual SQL statements.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     James Pepin <james@jamespepin.com>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 */
class Horde_Db_StatementParser implements Iterator
{
    protected $_count = 0;
    protected $_currentStatement;

    public function __construct($file)
    {
        if (is_string($file)) {
            $file = new SplFileObject($file, 'r');
        }
        $this->_file = $file;
    }

    public function current()
    {
        if (is_null($this->_currentStatement)) {
            $this->rewind();
        }
        return $this->_currentStatement;
    }

    public function key()
    {
        if (is_null($this->_currentStatement)) {
            $this->rewind();
        }
        return $this->_count;
    }

    public function next()
    {
        if ($statement = $this->_getNextStatement()) {
            $this->_count++;
            return $statement;
        }
        return null;
    }

    public function rewind()
    {
        $this->_count = 0;
        $this->_currentStatement = null;
        $this->_file->rewind();
        $this->next();
    }

    public function valid()
    {
        return !$this->_file->eof() && $this->_file->isReadable();
    }

    /**
     * Read the next sql statement from our file. Statements are terminated by
     * semicolons.
     *
     * @return string The next SQL statement in the file.
     */
    protected function _getNextStatement()
    {
        $this->_currentStatement = '';
        while (!$this->_file->eof()) {
            $line = $this->_file->fgets();
            if (!trim($line)) { continue; }
            if (!$this->_currentStatement && substr($line, 0, 2) == '--') { continue; }

            $trimmedline = rtrim($line);
            if (substr($trimmedline, -1) == ';') {
                // Leave off the ending ;
                $this->_currentStatement .= substr($trimmedline, 0, -1);
                return $this->_currentStatement;
            }

            $this->_currentStatement .= $line;
        }

        return $this->_currentStatement;
    }

}
