<br />
<?php
// output the gallery
if ($row['parents']) {
    echo _("Parents") .  ':<ul>';
    foreach ($row['parents'] as $parent_id => $data) {
        echo '<li>' . News::dateFormat($data['publish']) . ' '
             . Horde::link(News::getUrlFor('news', $parent_id), _("Read"))
             . $data['title'] . '</a> (' . ($data['comments']> -1 ? $data['comments'] : 0) . ')';
    }
    echo '</ul>';
}
