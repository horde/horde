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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
     *                         needs.
     *
     * @return Horde_Tree  The singleton instance.
     * @throws Horde_Tree_Exception
     */
    public function getTree($name, $renderer, array $params = array())
    {
        $renderer = Horde_String::lower($renderer);
        $id = $name . '|' . $renderer;

        if (!isset($this->_instances[$id])) {
            switch ($renderer) {
            case 'html':
                $renderer = 'Horde_Core_Tree_Html';
                break;

            case 'javascript':
                $renderer = 'Horde_Core_Tree_Javascript';
                break;
            }

            $this->_instances[$id] = Horde_Tree::factory($name, $renderer, $params);
        }

        return $this->_instances[$id];
    }

}
