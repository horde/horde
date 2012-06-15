<?php
/**
 * This class collects the javascript files needed for inclusion in the
 * browser output.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Script_List implements Countable, Iterator
{
    /**
     * The list of script files to output.
     *
     * @var array
     */
    protected $_files = array();

    /**
     * The list of files that has been previously output.
     *
     * @var array
     */
    protected $_output = array();

    /**
     * The temporary file list used when iterating.
     *
     * @var array
     */
    protected $_tmp;

    /**
     * Adds the script file to the output.
     *
     * @param Horde_Script_File $file  Script file object.
     *
     * @return Horde_Script_File  The script file object.
     */
    public function add(Horde_Script_File $file)
    {
        $id = $file->hash;

        if (!isset($this->_files[$id]) && !isset($this->_output[$id])) {
            $this->_files[$id] = $file;
            $this->_output[$id] = true;
        }

        return $this->_files[$id];
    }

    /**
     * Clears the cached list of files to output.
     */
    public function clear()
    {
        $this->_files = array();
    }

    /* Countable methods. */

    public function count()
    {
        return count($this->_files);
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_tmp);
    }

    public function key()
    {
        return key($this->_tmp);
    }

    public function next()
    {
        next($this->_tmp);
    }

    public function rewind()
    {
        $files = array();

        foreach ($this->_files as $val) {
            $files[$val->priority][] = $val;
        }

        ksort($files);

        $this->_tmp = array();
        foreach ($files as $val) {
            $this->_tmp = array_merge($this->_tmp, $val);
        }
        reset($this->_tmp);
    }

    public function valid()
    {
        return !is_null(key($this->_tmp));
    }

}
