<h1 class="header" style="text-align:center">
<?php
echo $this->link(-1)->link(array('title' => _("Last Year"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Year\', \'' . sprintf('%04d', $this->year - 1) . '0101\');')) . Horde::img('nav/left.png', '<') . '</a>';
echo '<span id="view_title">' . htmlspecialchars($this->year) . '</span>';
echo $this->link(1)->link(array('title' => _("Next Year"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Year\', \'' . sprintf('%04d', $this->year + 1) . '0101\');')) . Horde::img('nav/right.png', '>') . '</a>';
?>
</h1>
