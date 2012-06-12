<?php
/**
 * This class represents an external script file to include.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Script_File_External extends Horde_Script_File
{
    /**
     * External scripts are not cached.
     */
    public $cache = null;

    /**
     * By default, put external scripts as very low priority so it doesn't
     * break-up caching collections (since a non-cached script will cause
     * separate cache files to be created).
     */
    public $priority = self::PRIORITY_LOW;

    /**
     * External URL.
     *
     * @param string
     */
    protected $_url;

    /**
     * Adds an external javascript script to the output.
     *
     * @param string $url  The URL to the external script file.
     */
    public function __construct($url)
    {
        $this->_app = 'horde';
        $this->_file = basename($url);
        $this->_url = $url;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'hash':
            return hash('sha1', $this->_url);

        case 'modified':
            return 0;

        case 'path':
            return null;

        case 'url':
        case 'url_full':
            return $this->_url;
        }

        return parent::__get($name);
    }

}
