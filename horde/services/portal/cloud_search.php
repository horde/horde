<?php
/**
 * Perform search request for the horde-wide tag cloud block.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @TODO: If/when more apps support the searchTags api calls, probably
 *        should not hardcode the supported apps.  Also should allow excluding
 *        of applications in the tag search
 *
 * @author Michael J. Rubinksy <mrubinsk@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintaks' => true));

$tag = Horde_Util::getFormData('tag');
$results = array();
foreach ($GLOBALS['registry']->listAPIs() as $api) {
    if ($GLOBALS['registry']->hasMethod($api . '/listTagInfo')) {
        try {
            $results = array_merge(
                $results, $registry->{$api}->searchTags(array($tag), 10, 0, '', $registry->getAuth()));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }
    }
}

echo '<div class="control"><strong>'
    . sprintf(_("Results for %s"), '<span style="font-style:italic">' . htmlspecialchars($tag) . '</span>')
    . '</strong>'
    . Horde::link('#', '', '', '', '$(\'cloudsearch\').hide();', '', '', array('style' => 'font-size:75%;'))
    . '(' . _("Hide Results") . ')</a></span></div><ul class="linedRow">';

foreach ($results as $result) {
    echo '<li class="linedRow"><span style="width:50%"> ' .
         (empty($result['icon']) ? Horde::img(Horde_Themes::img($result['app'] . '.png', $result['app'])) : '') .
         Horde::link($result['view_url'], '', '', '', '', '', '', array('style' => 'margin:4px')) .
         (empty($result['icon']) ? $result['title'] : '<img src="' . $result['icon'] . '" />') .
         '</a></span><span style="width:50%;font-style:italic;">' . $result['desc'] . '</span></li>';
}
echo '</ul>';
