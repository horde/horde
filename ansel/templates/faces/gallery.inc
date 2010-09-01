<h1 class="header"><?php echo $title ?></h1>
<?php echo $pager->render() ?>
<table class="striped" style="width: 100%">
 <tr>
    <th><?php echo _("Filename") ?></th>
    <th><?php echo _("Photo") ?></th>
    <th><?php echo _("Faces") ?></th>
 </tr>
 <?php
 $imageids = array();
 foreach ($images as $image):
    $imageids[$image->id] = $image->id;
 ?>
 <tr>
  <td style="text-align: center">
   <?php echo $image->filename; ?><br /><br />
   <?php if ($autogenerate): ?>
    <a href="javascript:void(0)" onclick="proccessImageFaces(<?php echo $image->id ?>, 1)">
    <?php echo $reloadimage . _("Reget faces for this photo") ?></a><br />
   <?php endif; ?>
   <?php echo $customurl->copy()->add(array('image' => $image->id, 'gallery' => $image->gallery))->link()?>
   <?php echo  $customimage .  _("Add a custom face range") ?></a><br />
  </td>
  <td style="text-align: center">
   <a href="<?php echo Ansel::getUrlFor('view', array('view' => 'Image', 'image' => $image->id, 'gallery' => $image->gallery)); ?>" target="_blank">
   <img src="<?php echo Ansel::getImageUrl($image->id, 'thumb'); ?>" alt="<?php echo htmlspecialchars($image->filename)?>" /></a>
  </td>
  <td><div id="processface<?php echo $image->id ?>" name="processface<?php echo $image->id ?>"><?php echo _("Loading...") ?></div></td>
 </tr>
 <?php endforeach; ?>
</table>
<?php echo $pager->render() ?>
<script type="text/javascript">
//<![CDATA[
var imgids = <?php echo Horde_Serialize::serialize($imageids, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()); ?>;

function proccessImageFaces(image_id, reget) {
    var url = '<?php echo Horde::url('faces/image.php'); ?>';
    $('processface' + image_id).innerHTML = '<?php echo _("Loading...") ?>';
    var params = {'reload':reget, 'image': image_id};
    new Ajax.Updater({success:'processface' + image_id},
                     url,
                     {
                        method: 'post',
                        parameters: params
                      });

}

function deleteFace(image_id, face_id) {
    var url = '<?php echo Horde::url('xrequest.php')->add(array('requestType' => 'EditFaces')); ?>';
    var params = {'image': image_id, face: face_id};
    new Ajax.Request(url + '/action=delete',
                     {
                         method: 'post',
                         parameters: params
                     });
    $('face' + face_id).remove();
}

function setFaceName(image_id, face_id) {
    var params = {face: face_id, 'image': image_id, facename: encodeURIComponent($F('facename' + face_id))};
    var url = '<?php echo Horde::url('xrequest.php')->add(array('requestType' => 'EditFaces')); ?>';
    new Ajax.Updater({success: 'face' + face_id},
                     url + '/action=setname',
                     {
                         method: 'post',
                         parameters: params
                     });
}

Event.observe(window, 'load', function() {
    for (i in imgids) {
        proccessImageFaces(i, 0);
    };
});

//]]>
</script>
