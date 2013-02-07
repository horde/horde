<!-- Image title/breadcrumbs -->
<h1 class="header" id="ansel-photoname"><?php echo Ansel::getBreadCrumbs($this->gallery) ?></h1>

<table style="width: 100%;">
 <tr>
  <td valign="top" style="background-color:<?php echo $this->background; ?>;">
    <!-- Image Actions -->
    <div class="control anselActions" style="text-align:center;">
    <?php if (!empty($this->urls['prop_popup'])): ?>
      <?php echo $this->contentTag('a', _("Properties"), array('target' => '_blank', 'onclick' => $this->urls['prop_popup'] . 'return false;', 'id' => 'image_properties_link')); ?> |
    <?php endif; ?>
    <?php if (!empty($this->urls['edit'])): ?>
      <?php echo $this->contentTag('a', _("Edit"), array('id' => 'image_edit_link', 'href' => $this->urls['edit'])); ?> |
    <?php endif; ?>
    <?php if (!empty($this->urls['delete'])): ?>
      <?php echo $this->contentTag('a', _("Delete"), array('href' => $this->urls['delete'], 'onclick' => 'return window.confirm(\'' . addslashes(sprintf(_("Do you want to permanently delete ''%s''?"), $this->resource->filename)) . '\');', 'id' => 'image_delete_link')) ?> |
    <?php endif; ?>
    <?php if (!empty($this->urls['ecard'])): ?>
      <?php echo $this->contentTag('a', _("Send an Ecard"), array('href' => $this->urls['ecard'], 'target' => '_blank', 'id' => 'image_ecard_link')) ?> |
    <?php endif; ?>
    <?php if (!empty($this->urls['download'])): ?>
      <?php echo $this->contentTag('a', _("Download Original Photo"), array('href' => $this->urls['download'], 'id' => 'image_download_link')) ?> |
    <?php endif; ?>
    <?php if (!empty($this->urls['report'])): ?>
      <?php echo $this->contentTag('a', _("Report"), array('href' => $this->urls['report'])) ?>
    <?php endif; ?>
    </div>

    <!-- Upper slide controls -->
    <div class="slideControls">
      <?php echo Horde::fullSrcImg('loading.gif', array('attr' => 'class="imgloading"'));?>
      <?php if (empty($this->hide_slideshow)): ?>
        <?php echo $this->contentTag('a', Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')), array('style' => "display:none;", 'href' => $this->urls['slideshow'], 'class' => 'ssPlay', 'title=' => _("Start Slideshow"))) ?>
      <?php endif; ?>
      <?php echo $this->contentTag('a', Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')), array('id' => 'PrevLink', 'href' => $this->prev_url));
        echo $this->contentTag('a', Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')), array('id' => 'NextLink', 'href' => $this->next_url));
        echo $this->contentTag('a', Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')), array('href' => $this->urls['gallery']));
      ?>
    </div>

    <!-- Main Image Container -->
    <div id="Container">
     <noscript>
        <?php echo $this->tag('img', array('src' => $this->urls['imgsrc'], 'alt' => $this->h($this->filename))) ?>
     </noscript>
     <?php echo Horde::img('blank.gif', '', array('id' => 'photodiv', 'width' => $this->geometry['width'], 'height' => $this->geometry['height'])) ?>
     <div id="CaptionContainer" style="width:<?php echo $this->geometry['width']?>px;">
       <p id="Caption" style="display:none;">
         <?php echo $this->caption ?>
     </p>
     </div>
    </div>

    <!-- Lower slide controls -->
    <div class="slideControls">
      <?php echo Horde::fullSrcImg('loading.gif', array('attr' => 'class="imgloading"'));?>
      <?php if (empty($this->hide_slideshow)): ?>
        <?php echo $this->contentTag('a', Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')), array('style' => "display:none;", 'href' => $this->urls['slideshow'], 'class' => 'ssPlay', 'title=' => _("Start Slideshow"))) ?>
      <?php endif; ?>
      <?php echo $this->contentTag('a', Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')), array('href' => $this->prev_url));
        echo $this->contentTag('a', Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')), array('href' => $this->next_url));
        echo $this->contentTag('a', Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')), array('href' => $this->urls['gallery']));
      ?>
    </div>

    <!-- Exif Display -->
    <?php if (!empty($this->exif)): ?><br class="spacer">
      <div id="exif">
        <table id="ansel-exif-table" class="box striped" cellspacing="0">
          <?php foreach ($this->exif as $elem): ?>
            <tr>
              <?php foreach ($elem as $field => $value): ?>
                <td><strong><?php echo $field ?></strong><td><?php echo $this->h($value) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach ?>
        </table>
      </div>
    <?php endif; ?>

    <!-- Comments -->
    <?php if (!empty($this->commentHtml)): ?>
      <div id="messagediv">
        <?php echo $this->commentHtml; ?>
      </div>
    <?php endif; ?>

  </td>

  <!-- Widgets -->
  <?php if ($this->view->countWidgets()): ?>
    <td width="20%" valign="top">
      <!-- Widgets -->
     <?php $this->view->renderWidgets() ?>
    </td>
  <?php endif ?>
 </tr>
</table>
