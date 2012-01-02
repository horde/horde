<?php
/**
 * The Horde_Core_Tree_Javascript:: class provides javascript rendering of a
 * tree.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Tree_Javascript extends Horde_Core_Tree_Html
{
    /**
     * Constructor.
     *
     * @param string $name   @see parent::__construct().
     * @param array $params  @see parent::__construct(). Additional options:
     * <pre>
     * 'jsvar' - The JS variable name to store the tree object in.
     *           DEFAULT: Instance name.
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        parent::__construct($name, $params);

        Horde::addScriptFile('hordetree.js', 'horde');

        /* Check for a javascript session state. */
        if (($session = $this->getOption('session')) &&
            isset($_COOKIE[$this->_instance . '_expanded'])) {
            /* Get current session expanded values. */
            $curr = call_user_func($session['get'], $this->_instance, '', Horde_Session::TYPE_ARRAY);

            /* Remove "exp" prefix from cookie value. */
            $exp = explode(',', substr($_COOKIE[$this->_instance . '_expanded'], 3));

            /* These are the expanded folders. */
            foreach (array_filter($exp) as $val) {
                call_user_func($session['set'], $this->_instance, $val, true);
            }

            /* These are previously expanded folders. */
            foreach (array_diff(array_keys($curr), $exp) as $val) {
                call_user_func($session['set'], $this->_instance, $val, false);
            }
        }
    }

    /**
     * Provide a simpler renderer to fallback to.
     *
     * @return string  The next best renderer.
     */
    public function fallback()
    {
        return 'Horde_Core_Tree_Html';
    }

    /**
     * Returns the tree.
     *
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $this->_static = $static;

        $opts = array(
            'extraColsLeft' => $this->_colsLeft,
            'extraColsRight' => $this->_colsRight,
            'header' => $this->_header,
            'nocookie' => !$this->getOption('session'),
            'options' => $this->_options,
            'target' => $this->_instance,

            'cookieDomain' => $GLOBALS['conf']['cookie']['domain'],
            'cookiePath' => $GLOBALS['conf']['cookie']['path'],

            'imgBlank' => $this->_images['blank'],
            'imgFolder' => $this->_images['folder'],
            'imgFolderOpen' => $this->_images['folderopen'],
            'imgLine' => $this->_images['line'],
            'imgJoin' => $this->_images['join'],
            'imgJoinBottom' => $this->_images['join_bottom'],
            'imgJoinTop' => $this->_images['join_top'],
            'imgPlus' => $this->_images['plus'],
            'imgPlusBottom' => $this->_images['plus_bottom'],
            'imgPlusOnly' => $this->_images['plus_only'],
            'imgMinus' => $this->_images['minus'],
            'imgMinusBottom' => $this->_images['minus_bottom'],
            'imgMinusOnly' => $this->_images['minus_only'],
            'imgNullOnly' => $this->_images['null_only'],
            'imgLeaf' => $this->_images['leaf'],

            'initTree' => $this->renderNodeDefinitions()
        );

        if (!($js_var = $this->getOption('jsvar'))) {
            $js_var = $this->_instance;
        }

        Horde::addInlineScript(array(
            'window.' . $js_var . ' = new Horde_Tree(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON) . ')'
        ), 'dom');

        return '<div id="' . $this->_instance . '"></div>';
    }

    /**
     * Check the current environment to see if we can render the HTML tree.
     * We check for DOM support in the browser.
     *
     * @return boolean  Whether or not this backend will function.
     */
    public function isSupported()
    {
        return $GLOBALS['browser']->hasFeature('dom');
    }

    /**
     * Returns just the JS node definitions as a string.
     *
     * @return object  Object with the following properties: 'is_static',
     *                 'nodes', 'root_nodes'.
     */
    public function renderNodeDefinitions()
    {
        $this->_buildIndents($this->_root_nodes);

        $result = new stdClass;
        $result->is_static = intval($this->_static);
        $result->nodes = $this->_nodes;
        $result->root_nodes = $this->_root_nodes;
        $result->files = array();

        foreach ($GLOBALS['injector']->getInstance('Horde_Script_Files')->listFiles() as $apps) {
            foreach ($apps as $file) {
                /* Ignore files that are already loaded before building the
                 * tree. */
                if ($file['f'] == 'prototype.js' ||
                    $file['f'] == 'hordetree.js' ||
                    $file['f'] == 'accesskeys.js') {
                    continue;
                }
                $result->files[] = (string)$file['u'];
            }
        }

        return $result;
    }
}
