<?php
$num_galleries = $GLOBALS['injector']
    ->getInstance('Ansel_Storage')
    ->getScope()
    ->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, array('category' => $group), null, false);

if (empty($group)) {
    $category_title = _("Unfiled");
} else {
    $category_title = $group;
}

$params = array('groupby' => 'category',
                'category' => $group,
                'view' => 'List');
if (empty($group)) {
    $params['special'] = 'unfiled';
}
$viewurl = Ansel::getUrlFor('view', $params, true);
$link = Horde::link($viewurl, $category_title, 'light');

$preview_gallery = array();
if ($num_galleries < 5) {
    $preview_gallery = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getScope()
        ->listGalleries(array('filter' => array('category' => $group)));
} else {
    $preview_gallery = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getScope()
        ->listGalleries(array('filter' => array('category' => $group),
                              'from' => rand(0, $num_galleries - 4),
                              'count' => 4));
}
$mini_count = 0;
?>

<td class="galleryGroupsTile" width="<?php echo round(100 / $prefs->getValue('tilesperrow')); ?>%" valign="top">
 <table>
  <tr>
<?php foreach ($preview_gallery as $galleryId => $gallery): ?>
   <td align="center">
    <?php if ($gallery->isOldEnough() && !$gallery->hasPasswd()): ?>
     <?php echo $link ?><img src="<?php echo Ansel::getImageUrl($gallery->getKeyImage('ansel_default'), 'mini', false, 'ansel_default'); ?>" alt="<?php echo htmlspecialchars($gallery->get('name'))?>" /></a>
    <?php else: ?>
     <?php echo $link . Horde::img('gallery-locked-mini.png') ?></a>
    <?php endif; ?>
   </td>
<?php if (!(++$mini_count % 2)) echo "  </tr>\n  <tr>\n" ?>
<?php endforeach; ?>
  </tr>
 </table>

 <p class="light">
  <strong><?php echo $link . $category_title ?></a></strong><br />
  <?php echo sprintf(ngettext("%d Gallery", "%d Galleries", $num_galleries), $num_galleries) ?>
 </p>
</td>

<?php if (!(++$count % $prefs->getValue('tilesperrow'))) echo "</tr>\n<tr>\n" ?>
