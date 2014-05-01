<div id="horde-page" style="<?php echo ($GLOBALS['registry']->nlsconfig->curr_rtl ? 'right:' : 'left:') . $prefs->getValue('sidebar_width') ?>px">

<!-- begin header -->
<div id="anselHeader">
    <!-- begin top bar -->
    <div class="horde-buttonbar">
     <ul>
       <li class="horde-icon">
         <a id="anselMenuImages" title="<?php echo _("Photos")?>"><?php echo _("Photos")?></a>
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
    <div id="anselViewImages" style="display;none;">
        <div id="anselImagesMain">
        </div>
    </div>
    <!-- End Images -->

    <!-- Galleries -->
    <div id="anselViewGalleries" style="display;none;">
        <div id="anselGalleriesMain">
          <div class="anselRow"></div>
        </div>
    </div>
    <!-- End Images -->
</div>
<!-- end body -->

</div>
<!-- end main area -->

</div>