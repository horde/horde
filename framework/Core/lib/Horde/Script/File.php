<?php
/**
 * This class represents a javascript script file to output to the browser.
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
 *
 * @property string $app
 * @property string $file
 * @property string $full_path
 * @property string $hash  Hash value of this file.
 * @property integer $modified  Last modification time of the file.
 * @property string $path  Full filesystem path to script.
 * @property string $tag
 * @property string $tag_full
 * @property Horde_Url $url  URL to script.
 * @property Horde_Url $url_full  Full URL to script.
 */
class Horde_Script_File
{
    /** Priority constants. */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_LOW = 3;

    /**
     * The cache group this file should be output in.
     *
     * @var string
     */
    public $cache = 'default';

    /**
     * Javascript variables that should be output to the page.
     *
     * @var array
     */
    public $jsvars = array();

    /**
     * Application.
     *
     * @var string
     */
    protected $_app;

    /**
     * Filename.
     *
     * @var string
     */
    protected $_file;

    /**
     * Priority.
     *
     * @var integer
     */
    protected $_priority = self::PRIORITY_NORMAL;

    /**
     * Adds a single javascript script to the output (if output has already
     * started), or to the list of script files to include in the output.
     *
     * @param string $file  The full javascript file name.
     * @param string $app   The application name. Defaults to the current
     *                      application.
     */
    public function __construct($file, $app = null)
    {
        $this->_app = is_null($app)
            ? $GLOBALS['registry']->getApp()
            : $app;
        $this->_file = $file;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'app':
            return $this->_app;

        case 'file':
            return $this->_file;

        case 'full_path':
            return $this->path . $this->_file;

        case 'hash':
            return hash('sha1', $this->_app . "\0" . $this->_file);

        case 'modified':
            return filemtime($this->full_path);

        case 'path':
            return '/';

        case 'priority':
            return $this->_priority;

        case 'tag':
        case 'tag_full':
            return '<script type="text/javascript" src="' .
                (($name == 'tag') ? $this->url : $this->url_full) .
                '"></script>';

        case 'url':
        case 'url_full':
            return $this->_url($this->_file, ($name == 'url_full'));
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'priority':
            if (in_array($value, array(self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW))) {
                $this->_priority = $value;
            }
            break;
        }
    }

    /**
     */
    public function __toString()
    {
        return $this->tag;
    }

    /**
     * Create a static javascript URL.
     *
     * @param string $file   File name.
     * @param boolean $full  Return full URL?
     *
     * @return Horde_Url  URL.
     */
    protected function _url($file, $full)
    {
        $url = Horde::url($file, $full, -1);

        /* Add cache-busting version param. */
        return empty($GLOBALS['conf']['cachejsparams']['url_version_param'])
            ? $url
            : $url->add('v', hash('md5', $GLOBALS['registry']->getVersion($this->app)));
    }

}
