<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<table>
 <tr valign="top">
  <td style="text-align: center">
   <?php echo $facename;?><br />
   <img src="<?php echo $faces->getFaceUrl($face['image_id'], $face_id) ?>" style="padding: 5px;" alt="<?php echo $facename?>" /><br />
   <?php echo sprintf(ngettext("%d gallery", "%d galleries", count($face['galleries'])), count($face['galleries'])); ?><br />
   <?php if ($GLOBALS['conf']['report_content']['driver']): ?>
   <?php echo Horde::url('faces/report.php')->add('face', $face_id)->link(
           array('class' => 'small',
                 'title' => _("Report abuse or submit a removal request"))) . _("Report") . '</a><br />'?>
   <?php endif; ?>
   <?php if ($conf['faces']['search'] && Horde_Util::loadExtension('libpuzzle')) {
        echo Horde::url('faces/search/image_search.php')->add('face_id', $face_id)->link(array('title' => _("Find similar"))) . _("Similar faces") . '</a>';
    } ?>
  </td>
  <td>
   <?php
    $style = Ansel::getStyleDefinition($prefs->getValue('default_gallerystyle'));
    foreach (array_keys($face['galleries']) as $gallery_id) {
        foreach ($face['galleries'][$gallery_id] as $imageId) {
            echo '<div style="float: left; text-align: center;"><img src="' . Ansel::getImageUrl($imageId, 'thumb', false, $style) . '" style="padding: 5px;" alt="' . $imageId . '" /><br />';
            echo '<a href="' . Ansel::getUrlFor('view', array('view' => 'Image',
                                                              'gallery' => $gallery_id,
                                                              'image' => $imageId));

            echo '">' . _("Photo") . '</a> / ';
            echo '<a href="' . Ansel::getUrlFor('view', array('view' => 'Gallery',
                                                              'gallery' => $gallery_id));
            echo '">' . _("Gallery") . '</a></div>';
        }
    }
    ?>
  </td>
 </tr>
</table>
