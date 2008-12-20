<?php $sortdirclass = $acts['sbt'] ? 'sortdown' : 'sortup' ?>
<table id="browse" cellspacing="0" class="browse sortable striped nowrap">
<thead>
 <tr>
  <th id="s<?php echo Horde_VC::SORT_NAME ?>"<?php if ($acts['sbt'] == Horde_VC::SORT_NAME) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget($url['name'], _("File"), 'sortlink', '', '', _("_File")) ?>
  </th>
  <th id="s<?php echo Horde_VC::SORT_REV ?>"<?php if ($acts['sbt'] == Horde_VC::SORT_REV) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget($url['rev'], _("Rev"), 'sortlink', '', '', _("_Rev")) ?>
  </th>
  <th id="s<?php echo Horde_VC::SORT_AUTHOR ?>"<?php if ($acts['sbt'] == Horde_VC::SORT_AUTHOR) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget($url['author'], _("Author"), 'sortlink', '', '', _("_Author")) ?>
  </th>
  <th id="s<?php echo Horde_VC::SORT_AGE ?>"<?php if ($acts['sbt'] == Horde_VC::SORT_AGE) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget($url['age'], _("Date"), 'sortlink', '', '', _("Da_te")) ?>
  </th>
  <th class="nosort"><?php echo _("Last Log") ?></th>
 </tr>
</thead>
