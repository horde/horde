<script type="text/javascript">
document.observe('dom:loaded', function() {
        <?php if (empty($this->_params['hide_slideshow'])): ?>
        $$('.ssPlay').each(function(n) { n.show();});
        <?php endif; ?>
        Event.observe($('photodiv'), 'load', function() {
                new Effect.Appear($('photodiv'), { duration: 0.5,
                                                   afterFinish: function() {$$('.imgloading').each(function(n) { n.setStyle({visibility: 'hidden'});});
                                                                            new Effect.Appear($('Caption'), { duration: 0.5 });
                                                                             }});
                nextImg = new Image();
                prvImg = new Image();
                nextImg.src = "<?php echo $nextImgUrl ?>";
                prvImg.src = "<?php echo $prvImgUrl ?>";
        });
        new Effect.Opacity('photodiv', {to: 0, duration: 0.5, afterFinish: function() {$('photodiv').src = "<?php echo $imageUrl ?>"} });

        // Arrow keys for navigation
        document.observe('keydown', arrowHandler);
});

function arrowHandler(e)
{
    if (e.altKey || e.shiftKey || e.ctrlKey) {
        return;
    }

    theElement = Event.element(e);
    switch (theElement.tagName) {
    case 'INPUT':
    case 'SELECT':
    case 'TEXTAREA':
        return;
    }
    switch (e.keyCode || e.charCode) {
    case Event.KEY_LEFT:
        if ($('PrevLink')) {
            document.location.href = $('PrevLink').href;
        }
        break;

    case Event.KEY_RIGHT:
        if ($('NextLink')) {
            document.location.href = $('NextLink').href;
        }
        break;
    }
}
</script>
<h1 class="header" id="PhotoName"><?php echo Ansel::getBreadCrumbs(); ?></h1>
<table style="width: 100%; border-collapse: collapse">
 <tr>
  <td valign="top" style="background-color:<?php echo $style['background']; ?>;">
    <?php
        $needSeperator = false;
        $actionHTML = '';
        if ($this->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT) && empty($this->_params['api'])) {
            $needSeperator = true;
            $actionHTML .= Horde::link('#', '', '', '_blank', Horde::popupJs(Horde::applicationUrl($imageActionUrl), array('params' => array('actionID' => 'modify', 'ret' => 'image'), 'urlencode' => true)) . 'return false;', '', '', array('id' => 'image_properties_link')) . _("Properties") . '</a>';
            $actionHTML .= ' | ' . Horde::link(Horde::applicationUrl(Horde_Util::addParameter($imageActionUrl, 'actionID', 'editimage')), '', '', '', '', '', '', array('id' => 'image_edit_link')) . _("Edit") . '</a>';
        }
        if ($this->gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE) && empty($this->_params['api'])) {
            $actionHTML .= ' | ' . Horde::link(Horde::applicationUrl(Horde_Util::addParameter($imageActionUrl, 'actionID', 'delete')), '', '', '', 'return window.confirm(\'' . addslashes(sprintf(_("Do you want to permanently delete ''%s''?"), $this->resource->filename)) . '\');', '', '', array('id' => 'image_delete_link')) . _("Delete") . '</a>';
        }
        if (!empty($conf['ecard']['enable']) && !empty($ecardurl)) {
            $actionHTML .= (empty($this->_params['api']) && $needSeperator ? ' | ' : '') . Horde::link($ecardurl, '', '', '_blank', '', '', '', array('id' => 'image_ecard_link')) . _("Send an Ecard"); $needSeperator = true . '</a>';
        }
        if ($this->gallery->canDownload()) {
            $actionHTML .= (empty($this->_params['api']) && $needSeperator ? ' | ' : '') . Horde::link(Horde::applicationUrl(Horde_Util::addParameter('img/download.php', 'image', $imageId), true), '', '', '', '', '', '', array('id' => 'image_download_link')) . _("Download Original Photo"); $needSeperator = true . '</a>';
        }
        if (empty($this->_params['api']) && $this->gallery->get('owner') != Horde_Auth::getAuth() &&
            !empty($GLOBALS['conf']['report_content']['driver']) &&
            (($conf['report_content']['allow'] == 'authenticated' && Horde_Auth::isAuthenticated()) ||
             $conf['report_content']['allow'] == 'all')) {

            $reporturl = Horde_Util::addParameter(
                Horde::applicationUrl('report.php'),
                                      array('gallery' =>  $this->gallery->id,
                                      'image' => $imageId));

            $actionHTML .=  ($needSeperator ? ' | ' : '' ) . Horde::link($reporturl) . _("Report") . '</a>';
        }
        if (strlen($actionHTML)) {
            echo '<div class="control anselActions" style="text-align:center;">' . $actionHTML . '</div>';
        }
?>
    <div class="slideControls">
     <?php echo Horde::fullSrcImg('loading.gif', array('dir' => $registry->getImageDir('horde'), 'attr' => 'class="imgloading"'));?>
     <?php
     if (empty($this->_params['hide_slideshow'])) {
         echo '<a style="display:none;" href="' . $slideshow_url . '" class="ssPlay" title="' . _("Start Slideshow") . '">' .
               Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')) . '</a>';
     }
     echo '<a href="' . $prev_url . '" alt="<" id="PrevLink" title="' . _("Previous") . '">' .
           Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')) . '</a>';
     echo '<a href="' . $next_url . '" alt=">" id="NextLink"  title="' . _("Next") . '">' .
           Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')) . '</a>';
     echo '<a href="' . $galleryurl . '" id="GalleryLink" title="' . _("Back to gallery") . '">' .
           Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')) . '</a>';
     ?>

    </div>
    <div id="Container">
     <noscript>
      <?php echo '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($this->resource->filename) . '" />' ?>
     </noscript>
     <?php echo Horde::img('blank.gif', '', array('id' => 'photodiv', 'width' => $geometry['width'], 'height' => $geometry['height']), $registry->getImageDir('horde')) ?>
     <div id="CaptionContainer" style="width:<?php echo $geometry['width']?>px;">
     <p id="Caption" style="display:none;">
       <?php echo Horde_Text_Filter::filter($this->resource->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
     </p>
     </div>
    </div>
    <div class="slideControls">
     <?php echo Horde::fullSrcImg('loading.gif', array('dir' => $registry->getImageDir('horde'), 'attr' => 'class="imgloading"'));?>
     <?php
     if (empty($this->_params['hide_slideshow'])) {
         echo '<a style="display:none;" href="' . $slideshow_url . '" class="ssPlay" title="' . _("Start Slideshow") . '">' .
               Horde::fullSrcImg('slideshow_play.png', array('attr' => 'alt="' .  _("Play") . '"')) . '</a>';
     }
     echo '<a href="' . $prev_url . '" alt="<" id="PrevLink" title="' . _("Previous") . '">' .
           Horde::fullSrcImg('slideshow_prev.png', array('attr' => 'alt="' . _("Previous") . '"')) . '</a>';
     echo '<a href="' . $next_url . '" alt=">" id="NextLink"  title="' . _("Next") . '">' .
           Horde::fullSrcImg('slideshow_next.png', array('attr' => 'alt="' . _("Next") . '"')) . '</a>';
     echo '<a href="' . $galleryurl . '" id="GalleryLink" title="' . _("Back to gallery") . '">' .
           Horde::fullSrcImg('galleries.png', array('attr' => 'alt="' . _("Back to gallery") . '"')) . '</a>';
     ?>
    </div>
    <?php
     if ($prefs->getValue('showexif')) {
             echo '<br class="spacer" /><div id="exif">' . $exifHtml . '</div>';
     } ?>
     <div id="messagediv">
      <?php echo $commentHtml; ?>
     </div>
  </td>
  <?php if ($this->countWidgets()): ?>
  <td width="20%" valign="top">
    <!-- Widgets -->
   <?php echo $this->renderWidgets() ?>
  </td>
  <?php endif ?>
 </tr>
</table>
