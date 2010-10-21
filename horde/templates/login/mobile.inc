<!DOCTYPE html>
<html>
    <head>
    <title><?php echo $title ?></title>
    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css">
    <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
</head>
<body>

<div data-role="page">

<div data-role="header"><h1><?php echo $title ?></h1></div>

<div data-role="content">

<form action="login.php" method="post">
 <input type="hidden" name="anchor_string" value="<?php echo htmlspecialchars(strval($vars->anchor_string)) ?>">
 <input type="hidden" name="app" value="<?php echo htmlspecialchars($vars->app) ?>">
 <input type="hidden" name="url" value="<?php echo htmlspecialchars($vars->url) ?>">

<?php
foreach ($loginparams as $key => $val) {
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
                echo '<option value="' . $k2 . '"' . (empty($v2['selected']) ? '' : ' selected="selected"') . '>' . $v2['name'] . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input id="' . $key . '" name="' . $key . '" ' . (($val['type'] == 'password') ? 'type="password" ' : '') . 'value="' . (isset($val['value']) ? $val['value'] : '') . '">';
        }
        echo '</div>';
        break;
    }
}
?>
<div data-role="fieldcontain">
 <input type="submit" data-theme="a" name="login_button" value="<?php echo htmlspecialchars($title) ?>">
</div>

</form>

</div>

<div data-role="footer"></div>

</div>

</body>
</html>
