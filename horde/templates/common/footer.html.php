<?php if ($this->sidebarLoaded): ?>
  </div>
<?php endif; ?>
<?php if ($this->outputJs): ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php endif; ?>
  <?php echo implode("\n", $this->notifications) ?>
 </body>
</html>
