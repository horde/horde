<?php

$block_name = _("Stop Watch");
$block_type = 'tree';

/**
 * $Horde: hermes/lib/Block/tree_stopwatch.php,v 1.4 2009/06/10 05:24:07 slusarz Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_hermes_tree_stopwatch extends Horde_Block {

    var $_app = 'hermes';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry, $prefs;

        require_once dirname(__FILE__) . '/../base.php';

        Horde::addScriptFile('popup.js', 'horde', true);

        $entry = Horde::applicationUrl('entry.php');
        $icondir = $registry->getImageDir();

        $tree->addNode($parent . '__start',
                       $parent,
                       _("Start Watch"),
                       $indent + 1,
                       false,
                       array('icon' => 'timer-start.png',
                             'icondir' => $icondir,
                             'url' => '#',
                             'onclick' => "popup('" . Horde::applicationUrl('start.php') . "', 400, 100); return false;"));

        $timers = @unserialize($prefs->getValue('running_timers', false));
        if ($timers) {
            foreach ($timers as $i => $timer) {
                $hours = round((float)(time() - $i) / 3600, 2);
                $tname = Horde_String::convertCharset($timer['name'], $prefs->getCharset()) . sprintf(" (%s)", $hours);
                $tree->addNode($parent . '__timer_' . $i,
                               $parent,
                               $tname,
                               $indent + 1,
                               false,
                               array('icon' => 'timer-stop.png',
                                     'icondir' => $icondir,
                                     'url' => Horde_Util::addParameter($entry, 'timer', $i),
                                     'target' => 'horde_main'));
            }
        }
    }

}
