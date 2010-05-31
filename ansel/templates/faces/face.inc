<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<table>
 <tr valign="top">
  <td style="text-align: center">
   <?php echo $facename;?><br />
   <img src="<?php echo $faces->getFaceUrl($face['image_id'], $face_id) ?>" style="padding: 5px;" alt="<?php echo $facename?>" /><br />
   <?php echo sprintf(ngettext("%d gallery", "%d galleries", count($face['galleries'])), count($face['galleries'])); ?><br />
   <?php if ($GLOBALS['conf']['report_content']['driver']): ?>
   <a href="<?php echo Horde_Util::addParameter(Horde::applicationUrl('faces/report.php'), 'face', $face_id) ?>" class="small" title="<?php echo _("Report abuse or submit a removal request") ?>"><?php echo _("Report") ?></a><br />
   <?php endif; ?>
   <?php if ($conf['faces']['search']) {
        echo '<a href="' . Horde_Util::addParameter(Horde::applicationUrl('faces/search/image_search.php'), 'face_id', $face_id) . '" title="' . _("Find similar") . '">' . _("Similar faces") . '</a>';
    }
    ?>
  </td>
  <td>
   <?php
    $styleDef = Ansel::getStyleDefinition($prefs->getValue('default_gallerystyle'));
    $style = $styleDef['name'];
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
