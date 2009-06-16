<?php
/**
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     James Pepin <james@bluestatedigital.com>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 */

/**
 * Class for parsing a stream into individual SQL statements.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     James Pepin <james@bluestatedigital.com>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 */
class Horde_Db_StatementParser implements Iterator
{
    private $_count;
    private $_currentStatement;

    public function __construct(SplFileObject $file)
    {
        $this->_file = $file;
        $this->_count = 0;
    }

    public function current()
    {
        return $this->_currentStatement;
    }

    public function key()
    {
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
        return $this->_file->rewind();
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
