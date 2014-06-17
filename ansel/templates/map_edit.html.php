<div class="header"><?php echo sprintf(_("Update position of %s"), $this->title) ?></div>
<div style="width:450px;float:left;">
  <div id="ansel_map" style="width:450px;height:450px;"></div>
</div>
<div style="float:left;width:250px;min-height:450px;">
 <div class="control">
  <h4><?php echo _("Locate image at:") ?></h4>
  <form>
   <input type="text" id="locationInput" name="locationInput" />
   <span id="locationInput_loading_img" style="display:none;"><?php echo $this->loadingImg ?></span>
   <input id="locationAction" value="<?php echo _("Find") ?>" class="button" type="submit" />
  </form>
 </div>
 <div class="ansel_location_sameas"><?php echo _("Click on a thumbnail to locate at the same point.") ?><br />
 <?php foreach ($this->imgs as $id => $data): ?>
   <?php if ($this->image_id != $id): ?>
   <?php $title = !empty($data['image_location'])
        ? Ansel::point2Deg($data['image_latitude'], true) . ' ' . Ansel::point2Deg($data['image_longitude'])
        : $data['image_location'];
        $url = new Horde_Url('#');
        echo $url->link(array('title' => $title, 'id' => 'geo_' . $id)) ?>
        <img src="<?php echo Ansel::getImageUrl($id, 'mini', true) ?>" alt="[thumbnail]" /></a>
   <?php endif; ?>
 <?php endforeach; ?>
 </div>
 <div class="control" style="vertical-align:bottom;">
  <div style="text-align:center;margin-top:6px;"><img src="<?php echo Ansel::getImageUrl($this->image_id, 'thumb', true) ?>" /></div>
  <div class="ansel_geolocation">
   <div id="ansel_locationtext">&nbsp;</div>
   <div id="ansel_latlng">&nbsp;</div>
  </div>
 </div>
</div>
<div class="clear"></div>
<div class="control">
 <input class="button" id="saveButton" type="submit" value="<?php echo _("Save") ?>" /><input class="button" type="submit" onclick="window.close();" value="<?php echo _("Return to image view") ?>" />
</div>