<?php echo $tabs->render(Horde_Util::getGet('search_faces', 'all')); ?>
<h1 class="header">
<div style="float: right">
<form>
<input name="name" type="text" value="<?php echo $name ?>" />
<input type="image" name="submit" src="<?php echo Horde_Themes::img('search.png') ?>" />
</form>
</div>
<?php echo $title ?>
</h1>
<?php

if (empty($results)) {
    echo _("No faces found");
} else {
    foreach ($results as $face_id => $face) {
        echo '<a href="' . Ansel_Faces::getLink($face) . '" title="' . $face['face_name'] . '">'
                . '<img src="' . $faces->getFaceUrl($face['image_id'], $face_id, 'mini')
                . '" style="padding: 5px;" alt="' . htmlspecialchars($face['face_name']) . '" /></a>';
    }
}
