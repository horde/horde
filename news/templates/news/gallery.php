<?php
// output the gallery
if (empty($row['gallery'])
    || !is_array($images = News::getGalleyImages($row['gallery']))) {
    return;
}

$path = $GLOBALS['registry']->get('webroot', 'horde') . '/vfs/.horde/ansel/';
$dim = $conf['images']['image_height'] . ',' . $conf['images']['image_width'];

echo '<br /><div style="text-lign: center;">';
foreach ($images as $image_id => $image) {
    $img_url = substr($image_id, -2) . '/%s/' . $image_id . '.jpg';
    echo "\n" . '<div class="imggallery">' .
            Horde::link('#', $image['name'], '', '', 'popup(\'' . $path . sprintf($img_url, 'screen') . '\',' . $dim . ')')  . "\n" .
            '<img src="' . $path . sprintf($img_url, 'thumb') . '" />' .
            $image['caption'].  '</a></div>';
}
echo '</div>';

