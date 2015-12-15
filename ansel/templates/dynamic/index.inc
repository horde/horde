<div id="horde-page" style="<?php echo ($GLOBALS['registry']->nlsconfig->curr_rtl ? 'right:' : 'left:') . $prefs->getValue('sidebar_width') ?>px">

  <!-- Uploader view -->
  <div id="anselViewUpload" style="display:none;">
    <div class="header">
    <?php echo _("Upload to: ") ?>
    <select id="ansel-gallery-select"></select>
    </div>
    <div id="anseluploader"></div>
  </div>
  <!-- End Uploader -->

  <!-- begin header -->
  <div id="anselHeader">
      <!-- begin top bar -->
      <div class="horde-buttonbar">
       <ul>
         <li class="horde-icon">
           <a id="anselMenuImages" title="<?php echo _("Photo Stream")?>"><?php echo _("All Photos")?></a>
         </li>
         <li class="horde-icon">
           <a id="anselMenuGalleries" title="<?php echo _("Galleries")?>"><?php echo _("Galleries")?></a></li>
         </li>
         <li class="horde-icon">
           <a id="anselMenuMap" title="<?php echo _("Map")?>"><?php echo _("Map")?></a></li>
         </li>
         <li class="horde-icon">
           <a id="anselMenuDate" title="<?php echo _("Date")?>"><?php echo _("Date")?></a></li>
         </li>
         <li class="horde-icon">
           <a id="anselMenuTags" title="<?php echo _("Tags")?>"><?php echo _("Tags")?></a></li>
         </li>
       </ul>
      </div>
      <!-- end top bar -->
  </div>
  <!-- end header -->

  <!-- begin main area -->
  <div id="anselMain">

    <!-- begin body -->
    <div id="anselBody">
      <div id="anselLoading" style="display:none"></div>
        <div id="anselSizer">&nbsp;</div>
        <!-- Images -->
        <div id="anselImagesTitle" class="smallheader"></div>
        <div id="anselViewImages" style="display;none;">
          <div id="anselImagesMain"></div>
        </div>
        <!-- End Images -->

        <!-- Galleries -->
        <div id="anselGalleriesTitle"></div>
        <div id="anselViewGalleries" style="display;none;">
          <div id="anselGalleriesMain"></div>
        </div>
        <!-- End Galleries -->

        <!-- Image view overlay -->
        <div id="anselViewImage" style="display:none;">
          <div id="imageviewmain" class="ansel-imageview-main">
            <div id="topbar" class="ansel-imageview-topbar">
             <?php echo Horde_Themes_Image::tag('nav/left-grey.png', array('alt' => _("Previous"), 'attr' => array('class' => 'ansel-imageview-prev'))) ?>
             <?php //echo Horde_Themes_Image::tag('expand.png', array('alt' => _("Play"))) ?>
             <?php //echo Horde_Themes_Image::tag('expand.png', array('alt' => _("Pause"))) ?>
             <?php echo Horde_Themes_Image::tag('nav/right-grey.png', array('alt' => _("Next"), 'attr' => array('class' => 'ansel-imageview-next'))) ?>
             <a id="imageviewclose" class="ansel-imageview-close"><?php echo Horde_Themes_Image::tag('close.png', array('alt' => _("Close"))) ?></a>
            </div>
            <div id="imageviewimage" class="ansel-imageview-image"></div>
            <div id = "bottombar" class="ansel-imageview-bar">
              <div id="AnselViewImageData"></div>
            </div>
          </div>
          <div id="AnselImageViewSub" class="ansel-imageview-sub">[metadata]</div>
        </div>
        <!-- End Image View Overlay -->

    </div>
    <!-- end body -->

  </div>
  <!-- end main area -->

</div>