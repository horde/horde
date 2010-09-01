<script type="text/javascript">
//<![CDATA[
SlideController.initialize(<?php echo $this->json(array('view_links' => true)) ?>, <?php echo $imageIndex ?>, "<?php echo $GLOBALS['registry']->get('webroot')?>", <?php echo $this->gallery->id ?>, "<?php echo $this->gallery->getName()?>");
//]]>

function stopSlideShow()
{
    location.href = SlideController.photos[SlideController.photoId][5];
}
</script>

<h1 class="header"><?php echo Ansel::getBreadcrumbs() ?></h1>
<table width="100%" cellspacing="0">
 <tr class="control">
  <td>
    <?php if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)): ?>
        <?php $url = new Horde_Url('#'); echo $url->link(array('onclick.raw' => $this->_urls['prop_popup'] . ' SlideController.pause(); return false;','id' => 'image_properties_link')) . _("Properties") ?></a>
      | <?php echo Horde::url($this->_urls['edit'])->link(array('id' => 'image_edit_link')) . _("Edit") ?></a>
    <?php endif; ?>
    <?php if ($this->gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)): ?>
      | <?php echo $this->_urls['delete']->link(array('id' => 'image_delete_link')) . _("Delete") ?></a>
    <?php endif; ?>
  </td>
  <td class="rightAlign">
<?php if (!empty($GLOBALS['conf']['ecard']['enable'])): ?>
    <?php echo $this->_urls['ecard']->link(array('target' => '_blank', 'onclick' => 'SlideController.pause();' . Horde::popupJs($this->_urls['ecard'], array('urlencode' => true)) . 'return false;', 'id' => 'image_ecard_link')) . _("Send an Ecard") ?></a> |
<?php endif; ?>
    <?php echo $this->_urls['download']->link(array('onclick' => 'SlideController.pause();', 'id' => 'image_download_link')) . _("Download Original Photo") ?></a>
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
      <?php echo Horde::img('blank.gif', '', array('id' => 'Photo', 'style' => 'display:none')) ?>
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

