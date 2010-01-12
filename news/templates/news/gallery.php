<?php
// output the gallery
if (empty($row['gallery'])
    || !is_array($images = News::getGalleyImages($row['gallery']))) {
    return;
}

$path = $GLOBALS['registry']->get('webroot', 'horde') . '/vfs/.horde/ansel/';

echo '<br /><div style="text-lign: center;">';
foreach ($images as $image_id => $image) {
    $img_url = substr($image_id, -2) . '/%s/' . $image_id . '.jpg';
    echo "\n" . '<div class="imggallery">' .
            Horde::link('#', $image['name'], '', '', Horde::popupJs($path . sprintf($img_url, 'screen'), array('width' => $conf['images']['image_width'], 'height' => $conf['images']['image_height'], 'urlencode' => true)). 'return false')  . "\n" .
            '<img src="' . $path . sprintf($img_url, 'thumb') . '" />' .
            $image['caption'].  '</a></div>';
}
echo '</div>';

