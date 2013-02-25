<?php echo $this->render('begin'); ?>

<?php if ($this->hasEdit): ?>
    <?php echo $this->contentTag('a', (empty($this->images) ? _("Find faces") : _("Edit faces")), array('href' => $this->editUrl, 'id' => 'edit_faces', 'class' => 'widget')); ?>
  | <?php echo $this->contentTag('a', _("Manual face selection"), array('href' => $this->manualUrl, 'class' => 'widget')) ?>
<?php endif; ?>

<!-- Main Widget Content Area -->
<div id="faces_widget_content">
<?php if (empty($this->images)): ?>
    <br /><em><?php echo _("No faces found") ?></em></div>
<?php endif; ?>
  <div id="faces-on-image">
    <?php foreach ($this->images as $face): ?>
        <?php echo Ansel_Faces::getFaceTile($face) ?>
        <div id="facediv<?php echo $face['face_id'] ?>" class="face-div" style="width:<?php echo $face['face_x2'] - $face['face_x1']?>px;margin-left:<?php echo $face['face_x1']?>px;height:<?php echo $face['face_y2'] - $face['face_y1']?>px;margin-top:<?php echo $face['face_y1']?>px;">
          <div id="facedivname<?php echo $face['face_id']?>" class="face-div-name" style="display:none;"><?php echo $this->h($face['face_name']) ?></div>
        </div>
    <?php endforeach; ?>
  </div>
</div>
<!-- End Main Widget Content Area -->

<?php echo $this->render('end'); ?>