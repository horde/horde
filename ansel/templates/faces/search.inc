<?php echo $tabs->render('image'); ?>
<h1 class="header"><?php echo $title ?></h1>
<table style="width: 100%">
 <tr valign="top">
  <td style="text-align:center;">
   <?php if ($face_id): ?>
     <?php $facename = htmlspecialchars($face['face_name']);
           echo $facename;
     ?>
     <br />
     <img src="<?php echo $faces->getFaceUrl($face['image_id'], $face_id) ?>" style="padding: 5px;" alt="<?php echo $facename?>" /><br />
     <?php echo sprintf(ngettext("%d photo", "%d photos", count($results)), count($results)); ?><br />
     <?php echo Horde::url('faces/report.php')->add(array('face', $face_id))->link(
             array('class' => 'small',
                   'title' => _("Report abuse or submit a removal request"))) . _("Report") . '</a>'?>
   <?php else: ?>
     <img src="<?php echo Horde::url('faces/search/img.php')->add('thumb', 1) ?>" style="padding: 5px;" alt="<?php echo $title ?>" style="width: 50px; height 50px;" /></a>
   <?php endif; ?>
  </td>
<td>
<?php
if (empty($results)) {
    echo _("No faces found");
} else {
    echo $pager->render();
    foreach ($results as $face_id => $face) {
        include ANSEL_TEMPLATES . '/tile/face.inc';
    }
    echo $pager->render();
}
?>
</td>
</tr>
</table>
