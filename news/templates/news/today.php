<?php

echo '<br /><div class="header">' . _("On this day") . '</div>';
$img = Horde::img('news.png', '', '', $registry->getImageDir('news'));
echo $img . ' ' . Horde::link(Horde_Util::addParameter($browse_url, 'date', $row['publish'])) . _("News of this day.") . '</a><br />';

if ($registry->hasInterface('schedul')) {
    $img = Horde::img('schedul.png', '', '', $registry->getImageDir('schedul'));
    $url = $registry->get('webroot', 'schedul') . '/browse.php';
    $url = Horde_Util::addParameter($url, array('actionID' => 'date', 'date' => $row['publish']));
    echo $img . ' ' . Horde::link($url) . _("Events on this day.") . '</a><br />';
}

