<div data-role="page" data-theme="b" id="dayview">
  <div data-role="header">
    <h1><?php echo _("Day")?></h1>
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
    <div data-role="navbar" class="ui-bar-b">
      <ul>
        <li><a href="#" class="ui-btn-active"><?php echo _("Day")?></a></li>
        <li><a href="#monthview"><?php echo _("Month")?></a></li>
        <li><a href="#overview"><?php echo _("Summary")?></a></li>
      </ul>
    </div>
    <div class="ui-bar-b kronolithDayHeader">
      <a href="#" class="kronolithPrevDay" data-icon="arrow-l" data-iconpos="notext"><?php echo _("Previous")?></a>
      <span class="kronolithDayDate"></span>
      <a href="#" data-icon="arrow-r" data-iconpos="notext" class="kronolithNextDay"><?php echo _("Next")?></a>
    </div>
  </div>
  <div data-role="content" class="ui-body"></div>
</div>
