<?php if ($this->count() > $prefs->getValue('perpage')): ?>
<div class="turbaPager">
<?php
$show = Horde_Util::getFormData('show', 'all');
$currentPage = Horde_String::upper($page);
if ($currentPage == '*') {
    echo '<strong>(' . _("All") . ')</strong>&nbsp;';
} else {
    echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($viewurl, array('page' => '*',
                                                                              'show' => $show)))) . _("All") . '</a>&nbsp;';
}
for ($i = 65; $i < 91; $i++) {
    $a = chr($i);
    if ($currentPage == $a) {
        echo '<strong>(' . $a . ')</strong>&nbsp;';
    } else {
        $link = Horde_Util::addParameter($viewurl, array('page' => $a,
                                                   'show' => $show));
        echo Horde::link(Horde::applicationUrl($link)) . $a . '</a>&nbsp;';
    }
}
?>
</div>
<?php endif; ?>
