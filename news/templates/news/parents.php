<br />
<?php
// output the gallery
if ($row['parents']) {
    echo _("Parents") .  ':<ul>';
    foreach ($row['parents'] as $parent_id => $data) {
        echo '<li>' . $news->dateFormat($data['publish']) . ' ' 
             . Horde::link(Util::addParameter($news_url, 'id', $parent_id), _("Read"))
             . $data['title'] . '</a> (' . ($data['comments']> -1 ? $data['comments'] : 0) . ')';
    }
    echo '</ul>';
}
