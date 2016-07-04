
<div class="horde-sidebar-split"></div>
<div id="anselMenu">
  <div>
    <h3>
      <span title="<?php echo _("Expand") ?>" class="horde-expand"><?php echo _("View") ?></span>
    </h3>
    <div id="anselNavView" style="display:none;">
      <div id="anselNavMe" class="horde-subnavi">
        <div class="horde-subnavi-icon">
          <a class="icon"></a>
        </div>
        <div class="horde-subnavi-point"><?php echo _("Mine") ?></div>
      </div>
      <div id="anselNavAll" class="horde-subnavi">
        <div class="horde-subnavi-icon">
          <a class="icon"></a>
        </div>
        <div class="horde-subnavi-point"><?php echo _("All") ?></div>
      </div>
      <!-- TODO -->
<!--       <div id="anselNavSubscribed" class="horde-subnavi">
        <div class="horde-subnavi-icon">
          <a class="icon"></a>
        </div>
        <div class="horde-subnavi-point"><?php echo _("Subscribed") ?></div>
      </div> -->
    </div>
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
  <div class="horde-sidebar-split"></div>
  <div>
  <h3>
    <span class="horde-expand" title="<?php echo _("Collapse") ?>"><?php echo _("User Galleries") ?></span>
  </h3>
  <div id="anselSidebarOtherGalleries" style="display:none;"></div>
</div>
