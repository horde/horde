<?php
/**
 * The Horde_Tree:: class provides a tree view of hierarchical information. It
 * allows for expanding/collapsing of branches.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree
{
    /* Display extra columns. */
    const EXTRA_LEFT = 0;
    const EXTRA_RIGHT = 1;

    /**
     * The preceding text, before the Horde_Tree instance name, used for
     * collapse/expand submissions.
     */
    const TOGGLE = 'ht_toggle_';

    /**
     * Attempts to return a concrete instance.
     *
     * @param string $name      The name of this tree instance.
     * @param string $renderer  Either the tree renderer driver or a full
     *                          class name to use.
     * @param array $params     Any additional parameters the constructor
     *                          needs.
     *
     * @return Horde_Tree  The newly created concrete instance.
     * @throws Horde_Tree_Exception
     */
    static public function factory($name, $renderer, $params = array())
    {
        $ob = null;

        /* Base drivers (in Tree/ directory). */
        $class = __CLASS__ . '_' . ucfirst($renderer);
        if (class_exists($class)) {
            $ob = new $class($name, $params);
        } else {
            /* Explicit class name, */
            $class = $renderer;
            if (class_exists($class)) {
                $ob = new $class($name, $params);
            }
        }

        if ($ob) {
            if ($ob->isSupported()) {
                return $ob;
            }

            return self::factory($name, $ob->fallback(), $params);
        }

        throw new Horde_Tree_Exception(__CLASS__ . ' renderer not found: ' . $renderer);
    }

}
