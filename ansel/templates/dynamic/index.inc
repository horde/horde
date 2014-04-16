<div id="horde-page" style="<?php echo ($GLOBALS['registry']->nlsconfig->curr_rtl ? 'right:' : 'left:') . $prefs->getValue('sidebar_width') ?>px">

<!-- begin header -->
<div id="anselHeader">

<!-- begin top bar -->
<div class="horde-buttonbar">
 <ul>
   <li>
     <a id="anselMyGalleries" title="<?php echo _("My Galleries")?>"><?php echo _("My Galleries")?></a></li>
   </li>
   <li>
     <a id="anselMyStream" title="<?php echo _("My Stream")?>"><?php echo _("My Stream")?></a>
   </li>
   <li>
     <a id="anselGroups" title="<?php echo _("Group Galleries")?>"><?php echo _("Groups")?></a>
   </li>
   <li>
     <a id="anselFollowing" title="<?php echo _("Following")?>"><?php echo _("Following")?></a>
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
<?php
// require __DIR__ .
?>
</div>
<!-- end body -->

</div>
<!-- end main area -->

</div>