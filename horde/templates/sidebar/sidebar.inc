<div class="sidebar" style="width:<?php echo $prefs->getValue('sidebar_width') ?>px">
 <div id="expandedSidebar"<?php if (!$show_sidebar): ?> style="display:none"<?php endif; ?>>
  <span id="expandButton" class="iconImg" title="<?php echo htmlspecialchars(_("Hide Sidebar")) ?>"></span>
  <div id="sidebarPanel">
   <div id="sidebarLoading" style="display:none"><?php echo _("Loading...") ?></div>
   <?php $tree->renderTree() ?>
  </div>
 </div>

 <div class="iconImg" id="hiddenSidebar"<?php if ($show_sidebar): ?> style="display:none"<?php endif; ?> title="<?php echo htmlspecialchars(_("Show Sidebar")) ?>"></div>
</div>
