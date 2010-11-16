<div data-role="page" id="overview">
  <div data-role="header">
    <h1><?php echo _("Summary")?></h1>
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
    <div data-role="navbar" class="ui-bar-b">
      <ul>
        <li><a href="#dayview"><?php echo _("Day")?></a></li>
        <li><a href="#monthview"><?php echo _("Month")?></a></li>
        <li><a href="#" class="ui-btn-active"><?php echo _("Summary")?></a></li>
      </ul>
    </div>
  </div>
  <div data-role="content" class="ui-body"></div>
  <div data-role="footer"></div>
</div>
