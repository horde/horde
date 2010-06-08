<div><?php printf(_("Welcome to %s"), htmlspecialchars($registry->get('name'))) ?></div>

<form action="login.php" method="post">
 <input type="hidden" name="anchor_string" value="<?php echo htmlspecialchars(strval($vars->anchor_string)) ?>" />
 <input type="hidden" name="app" value="<?php echo htmlspecialchars($vars->app) ?>" />
 <input type="hidden" name="url" value="<?php echo htmlspecialchars($vars->url) ?>" />

 <p>
<?php
    foreach ($loginparams as $key => $val) {
        switch ($val['type']) {
        case 'hidden':
            echo '<input type="hidden" name="' . $key . '" value="' . (isset($val['value']) ? $val['value'] : '') . '" />';
            break;

        case 'text':
        case 'password':
        case 'select':
            echo '<div><label for="' . $key . '"><em>' . $val['label'] . '</em> ';
            if ($val['type'] == 'select') {
                echo '<select id="' . $key . '" name="' . $key . '">';
                foreach ($val['value'] as $k2 => $v2) {
                    echo '<option value="' . $k2 . '"' . (empty($v2['selected']) ? '' : ' selected="selected"') . '>' . $v2['name'] . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input id="' . $key . '" name="' . $key . '" ' . (($val['type'] == 'password') ? 'type="password" ' : '') . 'value="' . (isset($val['value']) ? $val['value'] : '') . '" />';
            }
            echo '</label></div>';
            break;
        }
    }
?>
 </p>
 <p>
  <input type="submit" name="login_button" value="<?php echo htmlspecialchars($title) ?>" />
 </p>
</form>
