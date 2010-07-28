<div class="sidebar" style="width:<?php echo $prefs->getValue('sidebar_width') ?>px">
 <div id="expandedSidebar" style="overflow:hidden">
  <span id="expandButton" class="rightFloat"><?php echo Horde::img('hide_panel.png', null, array('title' => _("Collapse Sidebar"))) ?></span>
  <div id="sidebarPanel">
   <?php $tree->renderTree() ?>
  </div>
 </div>

 <div id="hiddenSidebar">
  <?php echo Horde::img('show_panel.png') ?>
 </div>
</div>
