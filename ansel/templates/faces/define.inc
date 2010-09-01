<?php echo $tabs->render('image'); ?>
<h1 class="header"><?php echo $title ?></h1>
<link href="<?php echo Horde_Themes::img() ?>/../cropper.css" rel="stylesheet" type="text/css" />

<script type="text/javascript">

function onEndCrop(coords, dimensions ) {
    $('x1').value = coords.x1;
    $('y1').value = coords.y1;
    $('x2').value = coords.x2;
    $('y2').value = coords.y2;
    $('width').value = dimensions.width;
    $('height').value = dimensions.height;
}

window.observe('load', function() {
    new Cropper.ImgWithPreview(
        'faceImage',
        { minWidth: <?php echo $conf['thumbnail']['width'] ?>,
          minHeight: <?php echo $conf['thumbnail']['height'] ?>,
          onEndCrop: onEndCrop,
          <?php if ($x1): ?>
          displayOnInit: true,
          onloadCoords: { x1: <?php echo $x1 ?>, y1: <?php echo $y1 ?>, x2: <?php echo $x2 ?>, y2: <?php echo $y2 ?> },
          <?php endif; ?>
          previewWrap: 'previewArea' }
    )
});

</script>

<div><img src="<?php echo Horde::url('faces/search/img.php'); ?>" alt="[icon]" id="faceImage" /></div>

<form method="post" action="<?php echo Horde::url('faces/search/image_save.php'); ?>">

<div class="control" style="width: <?php echo $conf['screen']['height'] ?>px">
 <input type="submit" name="submit" id="submit" value="<?php echo _("Search") ?>" />
 <input type="submit" name="submit" id="submit" value="<?php echo _("Cancel") ?>" />
 <input type="reset" name="reset" id="reset" value="<?php echo _("Reset") ?>" />
</div>
<table style="width: <?php echo $conf['screen']['height'] ?>px">
 <tr valign="top">
  <td style="text-align: center; width: 33%"><div id="previewArea"></div></td>
  <td>
   <table class="striped">
    <tr>
     <td>x1:</td>
     <td><input type="text" name="x1" id="x1" size="4" readonly="readonly" value="<?php echo $x1 ?>" /></td>
     <td>y1:</td>
     <td><input type="text" name="y1" id="y1" size="4" readonly="readonly" value="<?php echo $y1 ?>"/></td>
    </tr>
    <tr>
     <td>x2:</td>
     <td><input type="text" name="x2" id="x2" size="4" readonly="readonly" value="<?php echo $x2 ?>"/></td>
     <td>y2:</td>
     <td><input type="text" name="y2" id="y2" size="4" readonly="readonly" value="<?php echo $y2 ?>"/></td>
    </tr>
    <tr>
     <td><?php echo _("width") ?>:</td>
     <td><input type="text" name="width" id="width" size="4" readonly="readonly" value="<?php echo $width ?>"/></td>
     <td><?php echo _("height") ?></td>
     <td><input type="text" name="height" id="height" size="4" readonly="readonly" value="<?php echo $height ?>"/></td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</from>

