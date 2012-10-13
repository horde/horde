<div id="horde-page" style="left:<?php echo $prefs->getValue('sidebar_width') ?>px">

<!-- begin header -->
<div id="hermesHeader">

 <!-- begin top bar -->
 <div class="horde-buttonbar">
    <ul>
      <li class="horde-active"><a href="#" id="hermesNavTime"><?php echo _("Time Log")?></a></li>
      <li class="horde-icon"><a href="#" id="hermesNavSearch"><?php echo _("Search")?></a></li>
      <li class="horde-icon"><a href="#" id="hermesNavAdmin"><?php echo _("Admin") ?></a></li>
<!-- Not yet implemented
      <li class="horde-icon"><a href="#" id="hermesNavCostObjects"><?php echo _("Cost Objects")?></a></li>
      <li class="horde-icon"><a href="#" id="hermesNavClients"><?php echo _("Clients")?></a></li>
      <li class="horde-icon"><a href="#" id="hermesNavTypes"><?php echo _("Job Types")?></a></li>
-->
    </ul>
 </div>
 <!-- end top bar -->

</div>
<!-- end header -->

<!-- begin main area -->
<div id="hermesMain">

<?php require __DIR__ . '/timer.inc'; ?>

<!-- begin body -->
<div id="hermesBody">
  <div id="hermesLoading" style="display:none"></div>
<?php
require __DIR__ . '/timeedit.inc';
require __DIR__ . '/search.inc';
?>
</div>
<!-- end body -->

</div>
<!-- end main area -->

</div>