<?php foreach ($results as $face):?>
    <div id="face<?php echo $face['face_id']?>">
      <img src="<?php echo $autocreate ? $faces->getFaceUrl($image_id, $face['face_id']) : Horde::url('faces/img.php')->add('face', $face['face_id']); ?>" alt="<?php echo $face['face_id'] ?>" style="padding-bottom: 5px; padding-left: 5px; width: 50px; height: 50px;" />
      <input type="text" name="facename<?php echo $face['face_id'] ?>" id="facename<?php echo $face['face_id'] ?>" size="15" value="<?php echo $face['face_name'] ?>" />
      <a href="javascript:void(0)" onclick="AnselEditFaces.set({ image_id: '<?php echo $image_id ?>', face_id: '<?php echo $face['face_id'] ?>' })" title="<?php echo _("Save face name") . '">' . Horde::img('save.png')?></a>
      <a href="javascript:void(0)" onclick="AnselEditFaces.delete({ image_id: '<?php echo $image_id ?>', face_id: '<?php echo $face['face_id'] ?>')" title="<?php echo _("Delete face") . '">' . Horde::img('delete.png')?></a>
      <?php echo $customurl->copy()->add(array('face' => $face['face_id'], 'image' => $image_id, 'url' => (!empty($url) ? $url : '')))->link(array('title' => _("Edit face region"))) . Horde::img('edit.png')?></a>
    </div>
<?php endforeach;?>
