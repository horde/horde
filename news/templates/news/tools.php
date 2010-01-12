<?php

echo '<br /><div class="header">' . _("Tools") . '</div>';

$img = Horde::img('print.png', '', '', $registry->getImageDir('horde'));
echo $img . ' ' . Horde::link('javascript:window.print()') . _("Printer firendly") . '</a><br />';

$img = Horde::img('mime/pdf.png', '', '', $registry->getImageDir('horde'));
echo $img . ' ' . Horde::link(Horde_Util::addParameter(Horde::applicationUrl('pdf.php'), 'id', $id)) . _("PDF") . '</a><br />';

/* Bookmark link */
if ($registry->hasMethod('bookmarks/getAddUrl')) {
    $api_params = array(
        'url' => News::getUrlFor('news', $id, true),
        'title' => $row['title']);
    $url = $registry->call('bookmarks/getAddUrl', array($api_params));
    $img = Horde::img('trean.png', '', '', $registry->getImageDir('trean'));
    echo $img . ' ' . Horde::link($url) . _("Add to bookmarks.") . '</a><br />';
}

if ($registry->hasInterface('notes')) {
    $img = Horde::img('mnemo.png', '', '', $registry->getImageDir('mnemo'));
    $url = Horde_Util::addParameter(Horde::applicationUrl('note.php', true), 'id', $id);
    echo $img . ' ' . Horde::link($url) . _("Add to notes.") . '</a><br />';
}
