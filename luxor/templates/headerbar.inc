<div id="headerbar">
<div class="header">
<?php echo _("Location:") ?><small>
[ <?php echo Horde::link(Luxor::url()) . htmlspecialchars($source['name']) ?></a> ]
<?php

echo Luxor::whereMenu() . '</small>';

if (!empty($onb)): ?>
&nbsp; &nbsp; <em>(<?php echo _("Tracking Branch") ?> <strong><?php echo $fl->branches[$onb] ?></strong>)</em>
<?php endif;

if ($pathname == '/' && count($sources) > 1) {
    echo '&nbsp; &nbsp;';
    echo Luxor::sources();
} ?>
</div>
<?php if ($pathname == '/' && @is_file($conf['paths']['introText'])): ?>
<div class="box">
<?php @readfile($conf['paths']['introText']) ?>
</div>
<?php endif ?>
</div>
