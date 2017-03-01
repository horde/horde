<?php if (!empty($this->instance)): ?>
<div class="horde-content">
  <input id="location<?php echo $this->instance ?>" name="location<?php echo $this->instance ?>" />
  <input type="button" id="button<?php echo $this->instance ?>" class="horde-default" value="<?php echo _("Change Location") ?>" />
  <span style="display:none;" id="location<?php echo $this->instance ?>_loading_img">
    <?php echo Horde_Themes_Image::tag('loading.gif') ?>
  </span>
</div>
<?php endif ?>

<div>
<?php if (is_array($this->location)): ?>
  <?php printf(_("Several locations possible with the parameter: %s"), $this->requested_location) ?><br />
  <ul>
<?php foreach ($this->location as $location):?>
    <li><?php echo $location->city ?>, <?php echo $location->state ?> (<?php echo $location->code ?>)</li>
<?php endforeach ?>
  </ul>
<?php else: ?>
<?php if ($this->radar || $this->map): ?>
  <table class="hordeBlockWeather"><tr><td>
<?php endif ?>
  <div id="weathercontent<?php echo $this->instance ?>">
    <?php echo $this->render('block/weather_content') ?>
  </div>
<?php if ($this->radar || $this->map): ?>
  </td><td>
<?php if ($this->map): ?>
  <div style="display:none;width:100%;height:500px;" class="horde-block-weathermap" id="weathermaplayer_<?php echo $this->instance ?>">&nbsp;</div>
<?php else: ?>
  <?php echo $this->tag('img', array('src' => $this->radar)) ?>
<?php endif ?>
  </td></tr></table>
<?php endif ?>
<?php endif ?>
</div>
