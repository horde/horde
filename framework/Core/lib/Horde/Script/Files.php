<?php
/**
 * This class provides a centralized location to manage javascript files
 * needed for inclusion in Horde output.
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
class Horde_Script_Files
{
    /**
     * Automatically include prototypejs?
     *
     * @var boolean
     */
    public $prototypejs = true;

    /**
     * The list of script files to add.
     *
     * @var array
     */
    protected $_files = array(
        'horde' => array()
    );

    /**
     * Output full URLs?
     *
     * @var boolean
     */
    protected $_full = false;

    /**
     * The list of files we have already included.
     *
     * @var array
     */
    protected $_included = array();

    /**
     * Adds the javascript code to the output (if output has already started)
     * or to the list of script files to include.
     *
     * @param string $file  The full javascript file name.
     * @param array $opts   Additional options:
     *   - app: (string) The application name.
     *          DEFAULT: Currently pushed application
     *   - full: (boolean) Output a full URL?
     *           DEFAULT: false
     *   - no_output: (boolean) Don't output scripts if content has already
     *                been sent?
     *                DEFAULT: false
     */
    public function add($file, array $opts = array())
    {
        if ($file == 'prototype.js') {
            $this->prototype = true;
            return;
        }

        if (!empty($opts['full'])) {
            $this->_full = true;
        }

        if (($this->_add($file, isset($opts['app']) ? $opts['app'] : null) === false) ||
            !empty($opts['no_output']) ||
            !Horde::contentSent()) {
            return;
        }

        // If headers have already been sent, we need to output a <script>
        // tag directly.
        $this->includeFiles();
    }

    /**
     * Adds an external script file
     *
     * @param string $url  The url to the external script file.
     * @param string $app  The app scope.
     */
    public function addExternal($url, $app = null)
    {
        // Force external scripts under Horde scope to better avoid
        // duplicates, and to ensure they are loaded before other application
        // specific files
        $app = 'horde';

        // Don't include scripts multiple times.
        if (!empty($this->_included[$app][$url])) {
            return false;
        }

        $this->_included[$app][$url] = true;

        $this->_files[$app][] = array(
            'f' => basename($url),
            'u' => $url,
            'd' => false,
            'e' => true
        );
    }

    /**
     * Helper function to determine if given file needs to be output.
     *
     * @return boolean  True if the file needs to be output.
     */
    public function _add($file, $app)
    {
        global $registry;

        if (empty($app)) {
            $app = $registry->getApp();
        }

        // Don't include scripts multiple times.
        if (!empty($this->_included[$app][$file])) {
            return false;
        }
        $this->_included[$app][$file] = true;

        if ($file[0] == '/') {
            $url = Horde::url($registry->get('webroot', $app) . $file, $this->_full, -1);
            $path = $registry->get('fileroot', $app);
        } else {
            $url = Horde::url($registry->get('jsuri', $app) . '/' . $file, $this->_full, -1);
            $path = $registry->get('jsfs', $app) . '/';
        }

        $this->_files[$app][] = array(
            'd' => true,
            'f' => $file,
            'p' => $path,
            'u' => $url
        );

        return true;
    }

    /**
     * Output the list of javascript files needed.
     */
    public function includeFiles()
    {
        foreach ($this->listFiles() as $files) {
            foreach ($files as $file) {
                $this->outputTag($file['u']);
            }
        }

        $this->clear();
    }

    /**
     * Clears the cached list of files to output.
     */
    public function clear()
    {
        $this->_files = array();
    }

    /**
     * Prepares the list of javascript files to include.
     *
     * @return array  The list of javascript files.
     */
    public function listFiles()
    {
        /* If there is no javascript available, there's no point in including
         * the rest of the files. */
        if (!$GLOBALS['browser']->hasFeature('javascript')) {
            return array();
        }

        /* Add prototype.js? */
        if ($this->prototypejs) {
            if (!isset($this->_included['horde']['prototype.js'])) {
                $old = $this->_files['horde'];
                $this->_files['horde'] = array();
                $this->_add('prototype.js', 'horde');
                $this->_add('horde.js', 'horde');
                $this->_files['horde'] = array_merge($this->_files['horde'], $old);
            }

            /* Add accesskeys.js if access keys are enabled. */
            if ($GLOBALS['prefs']->getValue('widget_accesskey')) {
                $this->_add('accesskeys.js', 'horde');
            }
        } elseif (empty($this->_files['horde'])) {
            /* Remove horde entry if nothing in it (was added on creation to
             * ensure that it gets loaded first. */
            unset($this->_files['horde']);
        }

        return $this->_files;
    }

    /**
     * Outputs a script tag.
     *
     * @param string $src  The source URL.
     */
    public function outputTag($src)
    {
        echo '<script type="text/javascript" src="' . $src . "\"></script>\n";
    }

    /**
     * Has the file been included already?
     *
     * @param string $file   The full javascript file name.
     * @param string $app    The application name. Defaults to the current
     *                       application.
     *
     * @return boolean  True if file is already included.
     */
    public function isIncluded($file, $app = null)
    {
        if (empty($app)) {
            $app = $registry->getApp();
        }

        // Don't include scripts multiple times.
        return !empty($this->_included[$app][$file]);
    }

}
