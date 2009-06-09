<?php

echo '<div class="header">' . _("News data") . '</div>';
echo _("By") . ': ' .  Horde::link(Horde_Util::addParameter($browse_url, 'user', $row['user'])) . $row['user'] . '</a><br />';
echo _("On") . ': ' .  Horde::link(Horde_Util::addParameter($browse_url, 'publish', $row['publish'])) . News::dateFormat($row['publish']) . '</a><br />';
echo _("Category") . ': ' . Horde::link(Horde_Util::addParameter($browse_url, 'cid', $row['category1'])) . $GLOBALS['news_cat']->getName($row['category1']) . '</a><br />';

$plain = preg_replace('/\s\s+/', ' ', trim(strip_tags($row['content'])));
echo _("Chars") . ': ' . number_format(strlen($plain)) . '<br />';
echo _("Besed") . ': ' . number_format(substr_count($plain, ' ')) . '<br />';

if ($row['sourcelink']) {
    echo _("Source news") . ': ' .  Horde::externalUrl($row['sourcelink'], true) . _("Source news") . '</a><br />';
}

if ($row['source']) {
    $sources = $news->getSources(true);
    echo _("Source media") . ': ' .  Horde::externalUrl($sources[$row['source']]['source_url'], true) .
                                     $sources[$row['source']]['source_name'] . '</a><br />';
}
