<?php
header('Content-type: text/html; charset=UTF-8');
header('Vary: Accept-Language');
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars(Horde_Util::nonInputVar('title')) ?></title>
  <link type="text/css" rel="stylesheet" href="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/jquery.mobile/jquery.mobile.min.css" />
  <script type="text/javascript" src="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/jquery.mobile/jquery.min.js"></script>
  <script type="text/javascript">
  $(window.document).bind('mobileinit', function() {
      $.mobile.page.prototype.options.backBtnText = '<?php echo _("Back") ?>';
      $.mobile.loadingMessage = '<?php echo _("loading") ?>';
  });
  </script>
  <script type="text/javascript" src="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/jquery.mobile/jquery.mobile.min.js"></script>
