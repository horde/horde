<div class="header">
 <span class="rightFloat">
   <?php ($groups_perpage < $num_groups) ? printf(_("%d to %d of %d Groups"), $start, $end, $num_groups) : printf(ngettext("%d Group", "%d Groups", $num_groups), $num_groups) ?>
 </span>
<?php
printf(_("Galleries grouped by %s"), $groupby);
echo ' ' . Ansel::getUrlFor('group', array())->link() . Horde::img('reload.png', _("Refresh List")) ?></a>
</div>
<div class="anselActions widget">
  <?php echo _("Group by:") . ' ' . implode(' | ', $groupby_links) ?>
</div>

<table id="galleryGroups"><tr>
