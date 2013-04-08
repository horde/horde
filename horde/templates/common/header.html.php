<!DOCTYPE html<?php if (!$this->smartmobileView): ?> PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd"<?php endif; ?>>
<html<?php echo $this->htmlAttr ?>>
 <head>
  <?php if (extension_loaded('newrelic')) echo newrelic_get_browser_timing_header() ?>
  <?php $this->pageOutput->outputMetaTags(); ?>
  <?php $this->pageOutput->includeStylesheetFiles($this->stylesheetOpts); ?>
<?php if (!$this->minimalView): ?>
  <?php $this->pageOutput->includeFavicon(); ?>
  <?php $this->pageOutput->outputLinkTags(); ?>
<?php if ($this->outputJs): ?>
  <?php $this->pageOutput->includeScriptFiles(); ?>
  <?php $this->pageOutput->outputInlineScript(); ?>
<?php if ($this->smartmobileView): ?>
  <?php $this->pageOutput->outputSmartmobileFiles(); ?>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
  <title><?php echo $this->pageTitle ?></title>
 </head>

 <body<?php echo $this->bodyAttr ?>>
