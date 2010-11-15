<div id="message" data-role="page">
  <div data-role="header">
    <h1>My Subject</h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div class="ui-body ui-body-c">
    <strong>My Subject</strong><br>
    <small>Date, Time</small>
  </div>
  <div class="ui-body ui-body-c">
    <a href="#" style="float:right;margin:0" data-role="button" data-icon="arrow-d" data-iconpos="notext">Show more</a>
    From: Myself
  </div>
  <div data-role="content">
  Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
  </div>
</div>
