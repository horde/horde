<?php if ($view->sidebarLoaded): ?>
  </div>
<?php endif; ?>
<?php if ($this->outputJs): ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php endif; ?>
  <?php echo $view->notifications ?>
 </body>
</html>
