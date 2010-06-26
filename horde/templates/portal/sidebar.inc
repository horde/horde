<div class="sidebar" style="width:<?php echo (isset($prefs) ? $prefs->getValue('sidebar_width') : 150) ?>px">

<div id="expandedSidebar" style="overflow:hidden">
 <span id="expandButton" class="rightFloat"><?php echo Horde::link('#', _("Collapse Sidebar"), '', '', 'HordeSidebar.toggleMenuFrame(); return false;', _("Collapse Sidebar")) . Horde::img('hide_panel.png') . '</a>' ?></span>
 <div id="sidebarPanel">
  <?php $tree->renderTree() ?>
 </div>
</div>

<div id="hiddenSidebar">
 <?php echo Horde::link('#', '', '', '', 'HordeSidebar.toggleMenuFrame(); return false;') . Horde::img('show_panel.png') . '</a>' ?>
</div>

<script type="text/javascript">
var horde_sidebar_rtl = <?php echo $rtl ? 'true' : 'false' ?>,
    horde_sidebar_cols = <?php echo (isset($prefs) ? $prefs->getValue('sidebar_width') : 150) ?>,
    horde_sidebar_domain = '<?php echo htmlspecialchars($GLOBALS['conf']['cookie']['domain']) ?>',
    horde_sidebar_path = '<?php echo $GLOBALS['conf']['cookie']['path'] ?>',
    horde_sidebar_refresh = <?php echo intval($prefs->getValue('menu_refresh_time')) ?>,
    horde_sidebar_url = '<?php echo Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/portal/sidebar.php') ?>';
</script>

</div>
