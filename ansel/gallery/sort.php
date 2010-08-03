<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

/* If we aren't provided with a gallery, redirect to the gallery
 * list. */
$galleryId = Horde_Util::getFormData('gallery');
if (!isset($galleryId)) {
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
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

Horde::addInlineScript(array(
    'Sortable.create("sortContainer", { tag: "div", overlap: "horizontal", constraint: false })'
), 'dom');

Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('dragdrop.js', 'horde');

$title = sprintf(_("%s :: Sort"), $gallery->get('name'));
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
?>
<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?></h1>
<div class="instructions">
 <form action="sort.php" method="post">
  <?php echo Horde_Util::formInput() ?>
  <input type="hidden" name="gallery" value="<?php echo (int)$galleryId ?>" />
  <input type="hidden" name="action" value="Sort" />
  <input type="hidden" name="order" id="order" />
  <input type="hidden" name="year" value="<?php echo $date['year'] ?>" />
  <input type="hidden" name="month" value="<?php echo $date['month'] ?>" />
  <input type="hidden" name="day" value="<?php echo $date['day'] ?>" />
  <p>
   <?php echo _("Drag photos to the desired sort position.") ?>
   <input type="submit" onclick="$('order').value = Sortable.serialize('sortContainer', { name: 'order' });" class="button" value="<?php echo _("Done") ?>" />
  </p>
 </form>
</div>

<div id="sortContainer" style="background:<?php echo $style['background'] ?>">

<?php
$images = $gallery->getImages();
foreach ($images as $image) {
    $caption = empty($image->caption) ? htmlspecialchars($image->filename, ENT_COMPAT, $GLOBALS['registry']->getCharset()) : htmlspecialchars($image->caption, ENT_COMPAT, $GLOBALS['registry']->getCharset());
    echo '<div id="o_' . (int)$image->id . '"><a title="'
        . $caption . '" href="#">'
        . '<img src="' . Ansel::getImageUrl($image->id, 'thumb', false, $style['name']) . '" alt="' . htmlspecialchars($image->filename) . '" />'
        . '</a></div>';
}
echo '</div>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
