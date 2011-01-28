<?php
/**
 * A Horde_Injector:: based Horde_Tree:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Tree:: factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Tree
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Singleton instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the Horde_Tree:: instance.
     *
     * @param string $name     The name of this tree instance.
     * @param mixed $renderer  The type of tree renderer.
     * @param array $params    Any additional parameters the constructor
     *                         needs. Defined by this class:
     * <pre>
     * 'nosession' - (boolean) Don't store tree state in the session.
     *               DEFAULT: false
     * </pre>
     *
     * @return Horde_Tree_Base  The singleton instance.
     * @throws Horde_Tree_Exception
     */
    public function create($name, $renderer, array $params = array())
    {
        $lc_renderer = Horde_String::lower($renderer);
        $id = $name . '|' . $lc_renderer;

        if (!isset($this->_instances[$id])) {
            switch ($lc_renderer) {
            case 'html':
                $renderer = 'Horde_Core_Tree_Html';
                break;

            case 'javascript':
                $renderer = 'Horde_Core_Tree_Javascript';
                break;

            case 'simplehtml':
                $renderer = 'Horde_Core_Tree_Simplehtml';
                break;
            }

            if (empty($params['nosession'])) {
                $params['session'] = array(
                    'get' => array($this, 'getSession'),
                    'set' => array($this, 'setSession')
                );
            }

            $this->_instances[$id] = Horde_Tree::factory($name, $renderer, $params);
        }

        return $this->_instances[$id];
    }

    /**
     */
    public function getSession($instance, $id)
    {
        return $GLOBALS['session']->get('horde', 'tree-' . $instance . '/' . $id);
    }

    /**
     */
    public function setSession($instance, $id, $val)
    {
        $GLOBALS['session']->set('horde', 'tree-' . $instance . '/' . $id, $val);
    }

}
