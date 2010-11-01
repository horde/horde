<h1 class="header" style="text-align:center">
<?php
$tomorrow = $this->getTomorrow();
$yesterday = $this->getYesterday();
echo $this->link(-1)->link(array('title' => _("Previous day"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Day\', \'' . $yesterday->dateString() . '\');')) . Horde::img('nav/left.png', '<') . '</a>';
echo '<span id="view_title">' . htmlspecialchars($this->getTime($prefs->getValue('date_format'))) . '</span>';
echo $this->link(1)->link(array('title' => _("Next day"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Day\', \'' . $tomorrow->dateString() . '\');')) . Horde::img('nav/right.png', '>') . '</a>';
?>
</h1>
<table cellspacing="0" width="100%" class="striped">
