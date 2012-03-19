<html>
 <head>
  <title>PHP Extensions</title>
  <style type="text/css"><?php echo file_get_contents(__DIR__ . '/test.css') ?></style>
 </head>
 <body>
  <p>
   <a href="<?php echo $self_url->copy()->add('mode', 'test') ?>">&lt;&lt; Back to test.php</a>
  </p>

  <table width="100%">
   <tr>
    <td colspan="2" style="padding-bottom:10px;">
     <table>
      <tr>
       <td>PHP Version:</td><td><strong><?php echo PHP_VERSION ?></strong></td>
      </tr>
      <tr>
       <td>Server API:</td><td><strong><?php echo PHP_SAPI ?></strong></td>
      </tr>
      <tr>
       <td>Loaded Extensions:</td><td><strong><?php $extensions = @get_loaded_extensions(); echo count($extensions); ?></strong></td>
      </tr>
      <tr>
       <td>System:</td><td><strong><?php echo @php_uname() ?></strong></td>
      </tr>
     </table>
    </td>
   </tr>
   <tr class="black">
    <td><strong>Activated Extensions:</strong></td>
    <td>
<?php if ($ext_get): ?>
     <strong><?php echo htmlspecialchars($ext_get) ?> Function List:</strong>
<?php endif; ?>
    </td>
   </tr>
   <tr>
    <td valign="top" width="50%">
     <table width="100%">
<?php
    @sort($extensions);
    foreach ($extensions as $ext) {
        echo '<tr><td width="30%">' . $ext. '</td><td><a href="' . $self_url->copy()->add(array('ext' => $ext, 'mode' => 'extensions')) . '">Function List</a></td></tr>';
    }
?>
     </table>
    </td>
    <td valign="top" width="50%">
     <table>
<?php
    if ($ext_get) {
        $functions = @get_extension_funcs($ext_get);
        if (is_array($functions)) {
            sort($functions);
            foreach ($functions as $func) {
                echo '<tr><td><a href="http://www.php.net/manual/function.' . str_replace('_', '-', $func) . '.php" target="_blank">' . $func . '</a></td></tr>';
            }
        }
    }
?>
     </table>
    </td>
   </tr>
  </table>
 </body>
</html>
