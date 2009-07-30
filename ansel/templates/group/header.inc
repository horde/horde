<div class="header">
 <span class="rightFloat">
   <?php ($groups_perpage < $num_groups) ? printf(_("%d to %d of %d Groups"), $start, $end, $num_groups) : printf(ngettext("%d Group", "%d Groups", $num_groups), $num_groups) ?>
 </span>
<?php
printf(_("Galleries grouped by %s"), $groupby);
echo ' ' . Horde::link(Ansel::getUrlFor('group', array())) .
Horde::img('reload.png', _("Refresh List"), null, $registry->getImageDir('horde')); ?></a>
</div>
<div class="anselActions widget">
  <?php echo _("Group by:") . ' ' . implode(' | ', $groupby_links) ?>
</div>

<table id="galleryGroups"><tr>
