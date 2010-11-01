<?php
/**
 * Perform search request for the horde-wide tag cloud block.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.

 * @TODO: If/when more apps support the searchTags api calls, probably
 *        should not hardcode the supported apps.  Also should allow excluding
 *        of applications in the tag search
 *
 * @author Michael J. Rubinksy <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintaks' => true));

// If/when more apps support the searchTags api calls, we should probably
// find a better solution to putting the apps hardcoded like this.
// Should also probably
$apis = array('images', 'news');

$tag = Horde_Util::getFormData('tag');
$results = $registry->call('images/searchTags', array(array($tag)));
$results = array_merge($results, $registry->call('news/searchTags',
                                                 array(array($tag))));
echo '<div class="control"><strong>'
    . sprintf(_("Results for %s"), '<span style="font-style:italic">' . htmlspecialchars($tag) . '</span>')
    . '</strong>'
    . Horde::link('#', '', '', '', '$(\'cloudsearch\').hide();', '', '', array('style' => 'font-size:75%;'))
    . '(' . _("Hide Results") . ')</a></span></div><ul class="linedRow">';

foreach ($results as $result) {
    echo '<li class="linedRow">' .
         Horde::img(Horde_Themes::img($result['app'] . '.png', $result['app'])) .
         Horde::link($result['view_url'], '', '', '', '', '', '', array('style' => 'margin:4px')) .
         $result['title'] .
         '</a><span style="font-style:italic;"><div style="margin-left:10px;font-style:italic">' . $result['desc'] . '</div></li>';
}
echo '</ul>';
