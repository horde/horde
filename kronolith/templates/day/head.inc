<h1 class="header" style="text-align:center">
<?php
$tomorrow = $this->getTomorrow();
$yesterday = $this->getYesterday();
echo Horde::link($this->link(-1), _("Previous day"), 'iconNav', '', 'return ShowView(\'Day\', \'' . $yesterday->dateString() . '\');') . Horde::img('nav/left.png', '<', '', $GLOBALS['registry']->getImageDir('horde')) . '</a>';
echo '<span id="view_title">' . htmlspecialchars($this->getTime($prefs->getValue('date_format'))) . '</span>';
echo Horde::link($this->link(1), _("Next day"), 'iconNav', '', 'return ShowView(\'Day\', \'' . $tomorrow->dateString() . '\');') . Horde::img('nav/right.png', '>', '', $GLOBALS['registry']->getImageDir('horde')) . '</a>';
?>
</h1>
<table cellspacing="0" width="100%" class="striped">
