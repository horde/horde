<?php
/**
 * $Shout: shout/main/dialplan.php,v 1.0.0.1 2005/11/03 00:05:08 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

$dialplan = &$shout->getDialplan($context);

require_once 'Horde/Tree.php';
require_once 'Horde/Block.php';
require_once 'Horde/Block/Collection.php';

// Set up the tree.
$tree = &Horde_Tree::singleton('shout_dialplan_menu', 'javascript');

foreach ($dialplan as $linetype => $linedata) {
    switch($linetype) {
        case 'extensions':
            $tree->addNode('extensions', null, 'Extensions', null);
            foreach ($linedata as $extension => $priorities) {
                switch($extension) {
                    case 'i':
                        $nodetext = 'Invalid Extension';
                        break;
                    case 's':
                        $nodetext = 'Main';
                        break;
                    case 't':
                        $nodetext = 'Timeout';
                        break;
                    case 'o':
                        $nodetext = 'Operator';
                        break;
                    case 'h':
                        $nodetext = 'Hangup';
                        break;
                    default:
                        $nodetext = $extension;
                        break;
                    }
                $tree->addNode($extension, 'extensions', $nodetext, null);
                foreach ($priorities as $priority => $application) {
                    $tree->addNode("$extension-$priority", $extension, "$priority: $application", null);
                }
            }
            break;

        case 'includes':
            $tree->addNode('includes', null, 'Includes', null);
            foreach ($linedata as $include) {
                $url = Horde::applicationUrl('index.php?section=dialplan&context='.$include);
                $tree->addNode($include, 'includes', $include, null, true, array('url' => $url));
            }
            break;

        # TODO Ignoring ignorepat lines for now
        case 'barelines':
            $tree->addNode('barelines', null, 'Extra Settings', null);
            foreach ($linedata as $bareline) {
                $tree->addNode($bareline, 'barelines', $bareline, null);
            }
            break;
    }
}

$tree->renderTree();

// Horde::addScriptFile('httpclient.js', 'horde', true);
// Horde::addScriptFile('hideable.js', 'horde', true);
// require HORDE_TEMPLATES . '/common-header.inc';
// require HORDE_TEMPLATES . '/portal/sidebar.inc';


// require SHOUT_TEMPLATES . "/dialplan/dialplanlist.inc";