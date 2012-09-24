<?php if ($this->basicView): ?>
<?php if ($this->sidebar): ?>
<?php echo $this->sidebar ?>
<?php elseif ($this->pageOutput->topbar): ?>
</div>
</div>
<?php endif ?>
<?php endif ?>
<?php if ($this->pageOutput->topbar && !$this->smartmobileView): ?>
</div>
<?php endif; ?>
<?php if (!$this->minimalView): ?>
<?php if ($this->outputJs): ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php if ($this->smartmobileView): ?>
  <?php $this->pageOutput->outputSmartmobileFiles(); ?>
  <div id="horde-notification" style="display:none"></div>
<?php endif ?>
<?php endif ?>
<?php endif ?>
 </body>
</html>
