<?php if ($this->sidebarLoaded): ?>
  </div>
<?php endif; ?>
<?php if ($this->smartmobileView): ?>
  <div id="smartmobile-notification" data-role="dialog">
    <div data-role="header">
     <h1><?php echo _("Notice")?></h1>
    </div>
    <div data-role="content" class="ui-body">
     <ul id="horde-notification" data-role="listview"></ul>
   </div>
  </div>
<?php endif; ?>
<?php if ($this->outputJs): ?>
  <?php if ($this->smartmobileView): $this->pageOutput->outputMobileScript(); endif; ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php endif; ?>
  <?php echo implode("\n", $this->notifications) ?>
 </body>
</html>
