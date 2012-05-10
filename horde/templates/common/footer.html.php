<?php if ($this->sidebarLoaded): ?>
  </div>
<?php endif; ?>
<?php if (!$this->minimalView): ?>
<?php if ($this->outputJs): ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php if ($this->smartmobileView): ?>
  <?php $this->pageOutput->outputSmartmobileFiles(); ?>
  <div id="horde-notification" style="display:none"></div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
  <?php echo implode("\n", $this->notifications) ?>
 </body>
</html>
