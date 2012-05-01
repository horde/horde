<div data-role="page" data-theme="a">
 <div data-role="header" data-nobackbtn="true">
  <h1><?php echo $title ?></h1>
 </div>
 <div data-role="content" style="max-width:480px; margin:0 auto;">
  <form action="<?php echo Horde::url('login.php') ?>" method="post" data-ajax="false">
   <input type="hidden" name="anchor_string" value="<?php echo htmlspecialchars(strval($vars->anchor_string)) ?>">
   <input type="hidden" name="app" value="<?php echo htmlspecialchars($vars->app) ?>">
   <input type="hidden" name="url" value="<?php echo htmlspecialchars($vars->url) ?>">
   <input type="hidden" id="horde-login-post" name="login_post">

   <fieldset>
<?php
foreach ($loginparams as $key => $val) {
    if ($key == 'new_lang') {
        continue;
    }
    switch ($val['type']) {
    case 'hidden':
        echo '<input type="hidden" name="' . $key . '" value="' . (isset($val['value']) ? $val['value'] : '') . '">';
        break;

    case 'text':
    case 'password':
    case 'select':
        echo '<div data-role="fieldcontain"><label for="' . $key . '">' . $val['label'] . '</label>';
        if ($val['type'] == 'select') {
            echo '<select id="' . $key . '" name="' . $key . '">';
            foreach ($val['value'] as $k2 => $v2) {
                if (is_null($v2)) {
                    echo '<option value="" disabled="disabled">- - - - - - - - - -</option>';
                } else {
                    echo '<option value="' . $k2 . '"' . (empty($v2['selected']) ? '' : ' selected="selected"') . '>' . $v2['name'] . '</option>';
                }
            }
            echo '</select>';
        } else {
            echo '<input id="' . $key . '" name="' . $key . '" ' . (($val['type'] == 'password') ? 'type="password" ' : 'type="text" ') . 'value="' . (isset($val['value']) ? $val['value'] : '') . '">';
        }
        echo '</div>';
        break;
    }
}
?>
   </fieldset>

   <fieldset data-role="controlgroup">
    <input type="button" data-theme="a" id="horde-login-button" value="<?php echo htmlspecialchars($title) ?>">
   </fieldset>
  </form>
 </div>
</div>
