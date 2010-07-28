<?php
/**
 * Defines the AJAX interface for Horde.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * AJAX action: Update sidebar.
     *
     * @return stdClass  An object with the following entries:
     * <pre>
     * 'is_static'
     * 'nodes'
     * 'root_nodes'
     * </pre>
     */
    public function sidebarUpdate()
    {
        $sidebar = new Horde_Ui_Sidebar();
        $tree = $sidebar->getTree();

        $defs = $tree->renderNodeDefinitions();

        $result = new stdClass;
        $result->is_static = $defs['is_static'];
        $result->nodes = $defs['nodes'];
        $result->root_nodes = $defs['root_nodes'];

        return $result;
    }

}
