<?php
/**
 * The Horde_Tree_Renderer class contains constants and a factory for
 * the tree renderers.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Tree
 */
class Horde_Tree_Renderer
{
    /* Display extra columns. */
    const EXTRA_LEFT = 0;
    const EXTRA_RIGHT = 1;

    /**
     * Attempts to return a concrete instance.
     *
     * @param string $renderer  Either the tree renderer driver or a full
     *                          class name to use.
     * @param array $params     Any additional parameters the constructor
     *                          needs. Either 'name' or 'tree' must be
     *                          specified. Common parameters are:
     *   - name: (string) The name of this tree instance.
     *   - tree: (Horde_Tree) An existing tree object.
     *   - session: (array) Callbacks used to store session data. Must define
     *              two keys: 'get' and 'set'. Function definitions:
     *              (string) = get([string - Instance], [string - ID]);
     *              set([string - Instance], [string - ID], [boolean - value]);
     *              DEFAULT: No session storage
     *
     * @return Horde_Tree  The newly created concrete instance.
     * @throws Horde_Tree_Exception
     */
    static public function factory($renderer, $params = array())
    {
        if (!isset($params['tree']) && !isset($params['name'])) {
            throw new BadFunctionCallException('Either "name" or "tree" parameters must be specified.');
        }

        if (isset($params['tree'])) {
            $tree = $params['tree'];
            unset($params['tree']);
        } else {
            $tree = new Horde_Tree(
                $params['name'],
                isset($params['session']) ? $params['session'] : array());
            unset($params['name']);
        }
        unset($params['session']);

        $ob = null;

        /* Base drivers (in Tree/ directory). */
        $class = __CLASS__ . '_' . ucfirst($renderer);
        if (class_exists($class)) {
            $ob = new $class($tree, $params);
        } else {
            /* Explicit class name, */
            $class = $renderer;
            if (class_exists($class)) {
                $ob = new $class($tree, $params);
            }
        }

        if ($ob) {
            if ($ob->isSupported()) {
                return $ob;
            }

            $params['tree'] = $tree;
            return self::factory($ob->fallback(), $params);
        }

        throw new Horde_Tree_Exception('Horde_Tree renderer not found: ' . $renderer);
    }
}
