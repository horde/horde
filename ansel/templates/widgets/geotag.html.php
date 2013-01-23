<!-- Geotag Widget -->
<?php echo $this->render('begin'); ?>
<div id="ansel_geo_widget">
  <div id="ansel_map"></div>
  <?php if (!empty($this->isImageView) && empty($this->geodata)): ?>
    <?php if ($this->haveEdit): ?>
        <div class="ansel_location_sameas">
        <?php echo sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $this->addLink, '</a>');?>
        <?php foreach($this->imgs as $id => $data):?>
            <?php echo $data['add_link']?><img src="<?php echo Ansel::getImageUrl($id, 'mini', true)?>" alt="[image]" /></a>
        <?php endforeach;?>
        </div>
    <?php else: ?>
        <?php echo _("No location data present."); ?>
    <?php endif; ?>
  <?php elseif (!empty($this->isImageView)):?>
    <div class="ansel_geolocation">
    <div id="ansel_locationtext"></div>
    <div id="ansel_latlng"></div>
    <div id="ansel_relocate"></div><div id="ansel_deleteGeotag"></div>
    </div>
    <div id="ansel_map_small"></div>
  <?php else:?>
    <div class="ansel_locationtext"></div>
    <div class="ansel_map_small"></div>
  <?php endif; ?>
</div>
<?php echo $this->render('end'); ?>
<!-- End Geotag Widget -->