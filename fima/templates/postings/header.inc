<div class="header">
 <div <?php if ($pageOb['page_count'] > 1) { echo 'style="width:40%" '; } ?> class="leftFloat">
  <span dir="ltr"><?php echo htmlspecialchars($title) ?></span>
<?php if ($pageOb['mode'] == 'list'): ?>
  <?php echo Horde::link($pageOb['url'], _("Refresh List")) . Horde::img('reload.png', _("Refresh List")) ?> </a>
<?php if (!isset($_SESSION['fima_search'])): ?>
  <?php echo Horde::link(Horde_Util::addParameter(Horde::url('search.php'), 'search_type', $prefs->getValue('active_postingtype')), _("Search Postings")) . Horde::img('search.png', _("Search")) ?></a>
<?php else: ?>
  <?php echo Horde::link(Horde::url('search.php'), _("Edit Search Query")) . Horde::img('edit.png', _("Edit Search Query")) ?></a>
  <?php echo Horde::link(Horde_Util::addParameter($pageOb['url'], 'actionID', 'clear_search'), _("Clear Search Query")) . Horde::img('delete.png', _("Clear Search Query")) ?></a>
<?php endif; ?>
<?php endif; ?>
 </div>

<?php if ($pageOb['mode'] == 'list'): ?>
<?php if ($pageOb['page_count'] > 1): ?>
 <div style="width:20%" class="leftFloat">
  <?php printf(_("Page %d of %d"), $pageOb['page'], $pageOb['page_count']) ?>
 </div>
 <div style="width:40%" class="rightFloat rightAlign">
<?php else: ?>
 <div class="rightFloat rightAlign">
<?php endif; ?>
  <?php echo $pageOb['postings_count'] ?>
 </div>
<?php endif; ?>

<?php if ( $GLOBALS['browser']->isBrowser('msie') && $GLOBALS['browser']->getMajor() < 7): ?>
 <div class="clear" style="clear:none" /></div>
<?php else: ?>
 <div class="clear"></div>
<?php endif; ?>
</div>

<?php if ($pageOb['mode'] != 'list'): ?>
<form method="post" name="postings" action="<?php echo $pageOb['url'] ?>">
<?php Horde_Util::pformInput() ?>
<?php endif; ?>

