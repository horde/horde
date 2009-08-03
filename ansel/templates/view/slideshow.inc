<script type="text/javascript">
//<![CDATA[
SlideController.initialize(<?php echo $this->json() ?>, <?php echo $imageIndex ?>, "<?php echo $GLOBALS['registry']->get('webroot')?>", <?php echo $this->gallery->id ?>, "<?php echo $this->gallery->getName()?>");
//]]>

function stopSlideShow()
{
    location.href = "<?php echo Horde::applicationUrl($this->_urls['gallery'], true) ?>?image=" + SlideController.photos[SlideController.photoId][3] + "&view=Image&page=" + SlideController.photos[SlideController.photoId][4];
}
</script>

<h1 class="header"><?php echo Ansel::getBreadcrumbs() ?></h1>
<table width="100%" cellspacing="0">
 <tr class="control">
  <td>
    <?php if ($this->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)): ?>
        <?php echo Horde::link('#', '', '', '_blank', 'SlideController.pause();' . $this->_urls['prop_popup'], '', '', array('id' => 'image_properties_link')) . _("Properties") ?></a>
      | <?php echo Horde::link(Horde::applicationUrl($this->_urls['edit']), '', '', '', '', '', '', array('id' => 'image_edit_link')) . _("Edit") ?></a>
    <?php endif; ?>
    <?php if ($this->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE)): ?>
      | <?php echo Horde::link($this->_urls['delete'], '', '', '', 'return window.confirm(\'' . addslashes(sprintf(_("Do you want to permanently delete \"%s\"?"), $this->resource->filename)) . '\');', '', '', array('id' => 'image_delete_link')) . _("Delete") ?></a>
    <?php endif; ?>
  </td>
  <td class="rightAlign">

<?php if (!empty($conf['ecard']['enable'])): ?>
    <?php echo Horde::link($this->_urls['ecard'], '', '', '_blank', 'SlideController.pause();' . Horde::popupJs($ecardurl, array('urlencode' => true)) . 'return false;', '', '', array('id' => 'image_ecard_link')) . _("Send an Ecard") ?></a> |
<?php endif; ?>
    <?php echo Horde::link($this->_urls['download'], '', '', '', 'SlideController.pause();', '', '', array('id' => 'image_download_link')) . _("Download Original Photo") ?></a>
  </td>
 </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:<?php echo $this->_style['background']; ?>;">
 <tr>
  <td>
    <div class="slideControls">
     <?php
     echo '<a href="#" id="ssPlay" onclick="SlideController.play(); return false;" style="display:none;" title="' . _("Start Slideshow") . '">' . Horde::img('slideshow_play.png', _("Play")) . '</a>';
     echo '<a href="#" id="ssPause" onclick="stopSlideShow(); return false;" title="' . _("Pause Slideshow") . '">' . Horde::img('slideshow_pause.png', _("Pause Slideshow")) . '</a>';
     echo '<a href="#" id="PrevLink" onclick="SlideController.prev(); return false;"  title="' . _("Previous") . '">' .
           Horde::img('slideshow_prev.png', _("Previous")) . '</a>';
     echo '<a href="#" id="NextLink" onclick="SlideController.next(); return false;" title="' . _("Next") . '">' .
           Horde::img('slideshow_next.png', _("Next")) . '</a>';
     ?>
    </div>
   </td>
  </tr>
  <tr>
   <td style="margin:5px;height:<?php echo $GLOBALS['conf']['screen']['height'] ?>px;vertical-align:top;">
     <div id="Container">
      <?php echo Horde::img('blank.gif', '', array('id' => 'Photo', 'style' => 'display:none;', 'width' => $this->_geometry['width'], 'height' => $this->_geometry['height']), $registry->getImageDir('horde')) ?>
     </div>
   </td>
  </tr>
  <tr>
   <td>
     <div id="CaptionContainer" style="width:200px;">
      <p id="Caption"></p>
     </div>
   </td>
  </tr>
</table>

