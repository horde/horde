<?php
/**
 * The Horde_Core_Tree_Javascript:: class provides javascript rendering of a
 * tree.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
            /* Remove "exp" prefix from cookie value. */
            $nodes = explode(',', substr($_COOKIE[$this->_instance . '_expanded'], 3));

            /* Save nodes to the session. */
            $_SESSION[$session][$this->_instance]['expanded'] = array_combine(
                $nodes,
                array_fill(0, count($nodes), true)
            );
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
            'window.' . $js_var . ' = new Horde_Tree(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()) . ')'
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

        return $result;
    }

}
