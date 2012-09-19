<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "DTD/xhtml1-frameset.dtd">
<html>
 <head></head>
 <body>
  <div id="headerblock" class="fixed mimeHeaders mimeHeadersPrint">
<?php foreach ($this->headers as $v): ?>
   <div>
    <strong><?php echo $this->h($v['header']) ?>:</strong>
    <?php echo $this->h($v['value']) ?>
   </div>
<?php endforeach; ?>
  </div>
 </body>
</html>
