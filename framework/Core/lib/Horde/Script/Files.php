<?php
/**
 * The Horde_Script_Files:: class provides a coherent way to manage script
 * files for inclusion in Horde output.  This class is meant to be used
 * internally by Horde:: only.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Script_Files
{
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
     * Adds the javascript code to the output (if output has already started)
     * or to the list of script files to include.
     *
     * @param string $file   The full javascript file name.
     * @param string $app    The application name. Defaults to the current
     *                       application.
     * @param boolean $full  Output a full url?
     */
    public function add($file, $app = null, $full = false)
    {
        if (($this->_add($file, $app, $full) === false) ||
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
        // Force external scripts under Horde scope to better avoid duplicates,
        // and to ensure they are loaded before other application specific files
        $app = 'horde';

        // Don't include scripts multiple times.
        if (!empty($this->_included[$app][$url])) {
            return false;
        }

        $this->_included[$app][$url] = true;

        // Always add prototype.js.
        if (!isset($this->_included[$app]['prototype.js'])) {
            $this->add('prototype.js', 'horde');
        }

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
    public function _add($file, $app, $full)
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

        // Always add prototype.js.
        if (!isset($this->_included[$app]['prototype.js']) &&
            ($file != 'prototype.js')) {
            $this->add('prototype.js', 'horde', $full);
        }

        // Add localized string for popup.js
        if (($file == 'popup.js') && ($app == 'horde')) {
            Horde::addInlineJsVars(array(
                'Horde.popup_block_text' => Horde_Core_Translation::t("A popup window could not be opened. Your browser may be blocking popups.")
            ), array('onload' => 'dom'));
        }

        if ($file[0] == '/') {
            $url = Horde::url($registry->get('webroot', $app) . $file, $full, -1);
            $path = $registry->get('fileroot', $app);
        } else {
            $url = Horde::url($registry->get('jsuri', $app) . '/' . $file, $full, -1);
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

        /* Add accesskeys.js if access keys are enabled. */
        if ($GLOBALS['prefs']->getValue('widget_accesskey')) {
            $this->_add('accesskeys.js', 'horde', false);
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

}
