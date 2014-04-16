
<div class="horde-sidebar-split"></div>
<div id="anselMenu">
  <div id="anselNavBrowse" class="horde-subnavi">
    <div class="horde-subnavi-icon">
      <a class="icon"></a>
    </div>
    <div class="horde-subnavi-point"><?php echo _("Browse") ?></div>
  </div>
  <div class="horde-sidebar-split"></div>
    <div>
      <h3>
        <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Tags") ?></span>
      </h3>
      <div id="anselImageTags" style="display:none;">
        <?php foreach ($this->tags as $tag): ?>
          <div class="ansel-sidebar-tag"><?php echo $tag?></div>
        <?php endforeach ?>
      </div>
    </div>

</div>
