<?php

echo '<br /><div class="header">' . _("Tools") . '</div>';

$img = Horde::img('print.png', '', '', $registry->getImageDir('horde'));
echo $img . ' ' . Horde::link('javascript:window.print()') . _("Printer firendly") . '</a><br />';

$img = Horde::img('mime/pdf.png', '', '', $registry->getImageDir('horde'));
echo $img . ' ' . Horde::link(Util::addParameter(Horde::applicationUrl('pdf.php'), 'id', $id)) . _("PDF") . '</a><br />';

if ($registry->hasInterface('bookmarks')) {
    $img = Horde::img('trean.png', '', '', $registry->getImageDir('trean'));
    $url = $registry->get('webroot', 'trean') . '/add.php';
    $url = Util::addParameter($url, array('url' => Util::addParameter($news_url, 'id', $id),
                                          'title' => $row['title']));
    echo $img . ' ' . Horde::link($url) . _("Add to bookmarks.") . '</a><br />';
}

if ($registry->hasInterface('notes')) {
    $img = Horde::img('mnemo.png', '', '', $registry->getImageDir('mnemo'));
    $url = Util::addParameter(Horde::applicationUrl('note.php', true), 'id', $id);
    echo $img . ' ' . Horde::link($url) . _("Add to notes.") . '</a><br />';
}

