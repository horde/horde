<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

/* If we aren't provided with a gallery, redirect to the gallery
 * list. */
$galleryId = Horde_Util::getFormData('gallery');
if (!isset($galleryId)) {
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($galleryId);
} catch (Ansel_Excception $e) {
    $notification->push(_("There was an error accessing the gallery."), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}

if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
}

$style = $gallery->getStyle();
$date = Ansel::getDateParameter();
$gallery->setDate($date);

switch (Horde_Util::getPost('action')) {
case 'Sort':
    parse_str(Horde_Util::getPost('order'), $order);
    $order = $order['order'];
    foreach ($order as $pos => $id) {
        $gallery->setImageOrder($id, $pos);
    }

    $notification->push(_("Gallery sorted."), 'horde.success');
    $style = $gallery->getStyle();

    Ansel::getUrlFor('view',
                     array_merge(
                           array('view' => 'Gallery',
                                 'gallery' => $galleryId,
                                 'slug' => $gallery->get('slug')),
                           $date
                     ),
                     true)->redirect();
    exit;
}

$page_output->addInlineScript(array(
    'jQuery("#sortContainer").sortable()',
    'jQuery("#sortContainer").disableSelection()',
), true);
$title = sprintf(_("%s :: Sort"), $gallery->get('name'));
$page_output->header(array(
    'title' => _("Search Forums")
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
?>
<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<div class="instructions">
 <form action="<?php echo Horde::url('gallery/sort.php') ?>" method="post">
  <?php echo Horde_Util::formInput() ?>
  <input type="hidden" name="gallery" value="<?php echo (int)$galleryId ?>" />
  <input type="hidden" name="action" value="Sort" />
  <input type="hidden" name="order" id="order" />
  <input type="hidden" name="year" value="<?php echo $date['year'] ?>" />
  <input type="hidden" name="month" value="<?php echo $date['month'] ?>" />
  <input type="hidden" name="day" value="<?php echo $date['day'] ?>" />
  <p>
   <?php echo _("Drag photos to the desired sort position.") ?>
   <input type="submit" onclick="jQuery('#order').val(jQuery('#sortContainer').sortable('serialize', { key: 'order[]' }));" class="button" value="<?php echo _("Done") ?>" />
  </p>
 </form>
</div>

<div id="sortContainer" style="background:<?php echo $style->background ?>">

<?php
$images = $gallery->getImages();
foreach ($images as $image) {
    $caption = htmlspecialchars(empty($image->caption) ? $image->filename : $image->caption);
    echo '<div id="o_' . (int)$image->id . '"><a title="'
        . $caption . '" href="#">'
        . '<img src="' . Ansel::getImageUrl($image->id, 'thumb', false, $style) . '" alt="' . htmlspecialchars($image->filename) . '" />'
        . '</a></div>';
}
echo '</div>';
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script>jQuery.noConflict();</script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js" type="text/javascript"></script>
<?php
$page_output->footer();
