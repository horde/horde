<?php
/**
 * The Horde_Script_Files:: class provides a coherent way to manage script
 * files for inclusion in Horde output.  This class is meant to be used
 * internally by Horde:: only.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Core
 */
class Horde_Script_Files
{
    /**
     * The singleton instance.
     *
     * @var Horde_Script_Files
     */
    static protected $_instance;

    /**
     * The list of script files to add.
     *
     * @var array
     */
    protected $_files = array();

    /**
     * The list of files we have already included.
     *
     * @var array
     */
    protected $_included = array();

    /**
     * The list of javascript files to always load from Horde.
     *
     * @var array
     */
    protected $_fromhorde = array('prototype.js');

    /**
     * The list of javscript files in Horde that have prototypejs'd versions.
     *
     * @var array
     */
    protected $_ptversions = array('tables.js', 'stripe.js', 'tooltips.js');

    /**
     * Singleton.
     */
    static public function singleton()
    {
        if (!self::$_instance) {
            self::$_instance = new Horde_Script_Files();
        }

        return self::$_instance;
    }

    /**
     * Adds the javascript code to the output (if output has already started)
     * or to the list of script files to include.
     *
     * @param string $file     The full javascript file name.
     * @param string $app      The application name. Defaults to the current
     *                         application.
     * @param boolean $direct  Include the file directly without passing it
     *                         through javascript.php?
     * @param boolean $full    Output a full url
     */
    public function add($file, $app = null, $direct = false, $full = false)
    {
        $res = $this->_add($file, $app, $direct, $full);

        if (empty($res) || (!ob_get_length() && !headers_sent())) {
            return;
        }

        // If headers have already been sent, we need to output a <script>
        // tag directly.
        echo '<script type="text/javascript" src="' . $res['u'] . '"></script>' . "\n";
    }

    /**
     * Helper function to determine if given file needs to be output.
     */
    public function _add($file, $app, $direct, $full = false)
    {
        global $registry;

        if (empty($app)) {
            $app = $registry->getApp();
        }

        // Skip any js files that have since been deprecated.
        if (!empty($this->_ignored[$app]) &&
            in_array($file, $this->_ignored[$app])) {
            return false;
        }

        // Several files will always be the same thing. Don't distinguish
        // between loading them in different $app scopes; always load them
        // from Horde scope.
        if (in_array($file, $this->_fromhorde)) {
            $app = 'horde';
        }

        // Don't include scripts multiple times.
        if (!empty($this->_included[$app][$file])) {
            return false;
        }
        $this->_included[$app][$file] = true;

        // Explicitly check for a directly serve-able version of the script.
        $path = $GLOBALS['registry']->get('fileroot', $app);
        if (!$direct &&
            file_exists($file[0] == '/'
                        ? $path . $file
                        : $registry->get('jsfs', $app) . '/' . $file)) {
            $direct = true;
        }

        if ($direct) {
            if ($file[0] == '/') {
                $url = Horde::url($registry->get('webroot', $app) . $file,
                                  $full, -1);
            } else {
                $url = Horde::url($registry->get('jsuri', $app) . '/' . $file,
                                  $full, -1);
                $path = $registry->get('jsfs', $app) . '/';
            }

        } else {
            $path = $registry->get('templates', $app) . '/javascript/';
            $url = Horde::url(
                Horde_Util::addParameter(
                    $registry->get('webroot', 'horde') . '/services/javascript.php',
                    array('file' => $file, 'app' => $app)));
        }

        $out = $this->_files[$app][] = array('f' => $file, 'd' => $direct, 'u' => $url, 'p' => $path);
        return $out;
    }

    /**
     * Includes javascript files that are needed before any headers are sent.
     */
    public function includeFiles()
    {
        foreach ($this->listFiles() as $app => $files) {
            foreach ($files as $file) {
                echo '<script type="text/javascript" src="' . $file['u'] . '"></script>' . "\n";
            }
        }
    }

    /**
     * Prepares the list of javascript files to include.
     *
     * @return array
     */
    public function listFiles()
    {
        /* If there is no javascript available, there's no point in including
         * the rest of the files. */
        if (!$GLOBALS['browser']->hasFeature('javascript')) {
            return array();
        }

        $prototype = false;

        // Always include Horde-level scripts first.
        if (!empty($this->_files['horde'])) {
            foreach ($this->_files['horde'] as $file) {
                if ($file['f'] == 'prototype.js') {
                    $prototype = true;
                    break;
                }
            }

            /* Add general UI js library. */
            $this->_add('tooltips.js', 'horde', true);
            if (!$prototype) {
                $keys = array_keys($this->_files['horde']);
                foreach ($keys as $key) {
                    $file = $this->_files['horde'][$key];
                    if (in_array($file['f'], $this->_ptversions)) {
                        $this->_add('prototype.js', 'horde', true);
                        $prototype = true;
                        break;
                    }
                }
            }

            // prototype.js must be included before any script that uses it
            if ($prototype) {
                $keys = array_keys($this->_files['horde']);
                foreach ($keys as $key) {
                    $file = $this->_files['horde'][$key];
                    if ($file['f'] == 'prototype.js') {
                        unset($this->_files['horde'][$key]);
                        array_unshift($this->_files['horde'], $file);
                    }
                }
                reset($this->_files);
            }
        }

        /* Add accesskeys.js if access keys are enabled. */
        if ($GLOBALS['prefs']->getValue('widget_accesskey')) {
            $this->_add('prototype.js', 'horde', true);
            $this->_add('accesskeys.js', 'horde', true);
        }

        /* Make sure 'horde' entries appear first. */
        reset($this->_files);
        if (key($this->_files) == 'horde') {
            return $this->_files;
        }

        if (isset($this->_files['horde'])) {
            $jslist = array('horde' => $this->_files['horde']);
        } else {
            $jslist = array();
        }
        foreach ($this->_files as $key => $val) {
            if ($key != 'horde') {
                $jslist[$key] = $val;
            }
        }

        return $jslist;
    }

}
