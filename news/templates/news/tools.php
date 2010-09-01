<?php

echo '<br /><div class="header">' . _("Tools") . '</div>';

$img = Horde::img('print.png');
echo $img . ' ' . Horde::link('javascript:window.print()') . _("Printer firendly") . '</a><br />';

$img = Horde::img('mime/pdf.png');
echo $img . ' ' . Horde::link(Horde_Util::addParameter(Horde::url('pdf.php'), 'id', $id)) . _("PDF") . '</a><br />';

/* Bookmark link */
if ($registry->hasMethod('bookmarks/getAddUrl')) {
    $api_params = array(
        'url' => News::getUrlFor('news', $id, true),
        'title' => $row['title']);
    $url = $registry->call('bookmarks/getAddUrl', array($api_params));
    $img = Horde::img(Horde_Themes::img('trean.png', 'trean'));
    echo $img . ' ' . Horde::link($url) . _("Add to bookmarks.") . '</a><br />';
}

if ($registry->hasInterface('notes')) {
    $img = Horde::img(Horde_Themes::img('mnemo.png', 'mnemo'));
    $url = Horde_Util::addParameter(Horde::url('note.php', true), 'id', $id);
    echo $img . ' ' . Horde::link($url) . _("Add to notes.") . '</a><br />';
}
