<h1 class="header" style="text-align:center">
<?php
$prev = $this->getMonth(-1);
$next = $this->getMonth(1);
echo $this->link(-1)->link(array('title' => _("Last Month"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Month\', \'' . $prev->dateString() . '\');')) . Horde::img('nav/left.png', '<') . '</a>';
echo '<span id="view_title">' . htmlspecialchars($this->date->strftime('%B %Y')) . '</span>';
echo $this->link(1)->link(array('title' => _("Next Month"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Month\', \'' . $next->dateString() . '\');')) . Horde::img('nav/right.png', '>') . '</a>';
?>
</h1>

<table class="monthgrid nopadding" cellspacing="0">
<thead>
 <tr>
<?php if (!$prefs->getValue('week_start_monday')): ?>
  <th class="item"><?php echo _("Sunday") ?></th>
<?php endif; ?>
  <th class="item"><?php echo _("Monday") ?></th>
  <th class="item"><?php echo _("Tuesday") ?></th>
  <th class="item"><?php echo _("Wednesday") ?></th>
  <th class="item"><?php echo _("Thursday") ?></th>
  <th class="item"><?php echo _("Friday") ?></th>
  <th class="item"><?php echo _("Saturday") ?></th>
<?php if ($prefs->getValue('week_start_monday')): ?>
  <th class="item"><?php echo _("Sunday") ?></th>
<?php endif; ?>
 </tr>
</thead>
<tbody>
