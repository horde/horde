<?php

$block_name = _("Stop Watch");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_hermes_tree_stopwatch extends Horde_Block
{
    protected $_app = 'hermes';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry, $prefs;

        Horde::addScriptFile('popup.js', 'horde', true);

        $entry = Horde::applicationUrl('entry.php');

        $tree->addNode($parent . '__start',
                       $parent,
                       _("Start Watch"),
                       $indent + 1,
                       false,
                       array('icon' => 'timer-start.png',
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
                                     'url' => Horde_Util::addParameter($entry, 'timer', $i)));
            }
        }
    }

}
