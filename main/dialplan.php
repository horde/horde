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
            $url = '#top';
            $tree->addNode('extensions', null, 'Extensions', null, array('url' => $url));
            foreach ($linedata as $extension => $priorities) {
                $nodetext = Shout::exten2name($extension);
                $url = Horde::applicationUrl('index.php?section=dialplan' .
                    '&extension=' . $extension . '&context=' . $context);
                $url = "#$extension";
                $tree->addNode("extension_".$extension, 'extensions', $nodetext,
                    null, false,
                    array(
                        'url' => $url,
                        'onclick' => 'dp.highlightExten(\''.$extension.'\')',
                    )
                );
//                 foreach ($priorities as $priority => $application) {
//                     $tree->addNode("$extension-$priority", $extension, "$priority: $application", null);
//                 }
            }
            break;

        case 'includes':
            $tree->addNode('includes', null, 'Includes', null);
            foreach ($linedata as $include) {
                $url = Horde::applicationUrl('index.php?section=dialplan&context='.$include);
                $tree->addNode("include_$include", 'includes', $include, null,
                    true, array('url' => $url));
            }
            break;

        # TODO Ignoring ignorepat lines for now

        case 'barelines':
            $tree->addNode('barelines', null, 'Extra Settings', null);
            $i = 0;
            foreach ($linedata as $bareline) {
                $tree->addNode("bareline_".$i, 'barelines', $bareline, null);
                $i++;
            }
            break;
    }
}

require SHOUT_TEMPLATES . '/dialplan/contexttree.inc';
require SHOUT_TEMPLATES . '/dialplan/extensiondetail.inc';

// Horde::addScriptFile('httpclient.js', 'horde', true);
// Horde::addScriptFile('hideable.js', 'horde', true);
// require HORDE_TEMPLATES . '/common-header.inc';
// require HORDE_TEMPLATES . '/portal/sidebar.inc';


// require SHOUT_TEMPLATES . "/dialplan/dialplanlist.inc";