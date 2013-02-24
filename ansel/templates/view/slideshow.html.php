<!-- Image title/breadcrumbs -->
<h1 class="header"><?php echo Ansel::getBreadcrumbs() ?></h1>

<div id="ansel-contentwrapper">
  <div id="ansel-content-nowidgets" style="background-color:<?php echo $this->background; ?>;">
    <!-- Actions -->
    <div class="control anselActions" style="text-align:center;">
      <?php if ($this->hasEdit): ?>
        <?php echo $this->contentTag('a', _("Properties"), array('target' => '_blank', 'id' => 'image_properties_link')); ?> |
       | <?php echo $this->contentTag('a', _("Edit"), array('id' => 'image_edit_link', 'href' => $this->urls['edit'])); ?> |
      <?php endif; ?>
      <?php if (!empty($this->urls['delete'])): ?>
        <?php echo $this->contentTag('a', _("Delete"), array('href' => $this->urls['delete'], 'onclick' => 'return window.confirm(\'' . addslashes(sprintf(_("Do you want to permanently delete ''%s''?"), $this->resource->filename)) . '\');', 'id' => 'image_delete_link')) ?> |
      <?php endif; ?>
      <?php if (!empty($this->urls['ecard'])): ?>
        <?php echo $this->contentTag('a', _("Send an Ecard"), array('href' => $this->urls['ecard'], 'onclick' => 'SlideController.pause();', 'target' => '_blank', 'id' => 'image_ecard_link')) ?> |
      <?php endif; ?>
      <?php if (!empty($this->urls['download'])): ?>
        <?php echo $this->contentTag('a', _("Download Original Photo"), array('href' => $this->urls['download'], 'onclick' => 'SlideController.pause();', 'id' => 'image_download_link')) ?> |
      <?php endif; ?>
    </div>

    <!-- Upper Slide Controlls -->
    <div class="slideControls">
      <?php echo $this->contentTag('a', Horde::img('slideshow_play.png', _("Play")), array('id' => 'ssPlay', 'style' => 'display:none;', 'title' => _("Start Slideshow"))); ?>
      <?php echo $this->contentTag('a', Horde::img('slideshow_pause.png', _("Pause")), array('id' => 'ssPause' , 'title' => _("Pause Slideshow"))); ?>
      <?php echo $this->contentTag('a', Horde::img('slideshow_prev.png', _("Previous")), array('id' => 'PrevLink', 'title' => _("Previous"))); ?>
      <?php echo $this->contentTag('a', Horde::img('slideshow_next.png', _("Next")), array('id' => 'NextLink', 'title' => _("Next"))); ?>
    </div>
    <div id="anselimagecontainer">
      <?php echo Horde::img('blank.gif', '', array('id' => 'Photo', 'width' => $this->geometry['width'], 'height' => $this->geometry['height'])) ?>
      <div id="CaptionContainer" style="width:<?php echo $this->geometry['width']?>px;">
        <p id="Caption">
          <?php echo $this->caption ?>
        </p>
      </div>
    </div>
  </div>
</div>
