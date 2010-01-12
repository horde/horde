<?php if ($pageOb['mode'] == 'shift' || $pageOb['mode'] == 'transfer'): ?>
<tr class="control"><td colspan="6">
<table cellspacing="0" width="100%" class="linedRow">
<?php return; endif; ?>
<?php
if (!isset($headers_inc_count)) {
    $headers_inc_count = 0;
} else {
    $headers_inc_count++;
}
$sortImg = ($sortdir) ? 'za.png' : 'az.png';
$sortText = ($sortdir) ? '\/' : '/\\';
$headers = array(
    FIMA_SORT_DATE => array(
        'stext' => _("Sort by Date"),
        'text' => _("Da_te"),
        'width' => '90'
    ),
    FIMA_SORT_ASSET => array(
        'stext' => _("Sort by Asset Account"),
        'text' => _("A_sset Account"),
        'width' => '33%'
    ),
    FIMA_SORT_ACCOUNT => array(
        'stext' => _("Sort by Posting Account"),
        'text' => _("Posting A_ccount"),
        'width' => '33%'
    ),
    FIMA_SORT_DESC => array(
        'stext' => _("Sort by Description"),
        'text' => _("Descriptio_n"),
        'width' => '33%'
    ),
    FIMA_SORT_AMOUNT => array(
        'stext' => _("Sort by Amount"),
        'text' => _("Amo_unt"),
        'width' => '100'
    )
);

if ($pageOb['mode'] == 'edit') {
    $headers[] = array(
        'text' => _("e.o."),
        'width' => '25'
    );
}

?>
<?php if ($pageOb['mode'] == 'list'): ?>
<form method="post" name="postings" action="<?php echo $pageOb['url'] ?>">
<?php Horde_Util::pformInput() ?>
<?php endif; ?>
<table class="postingList" width="100%" cellspacing="0">
 <tr class="item">
<?php if ($pageOb['mode'] == 'list'): ?>
<?php if (!$headers_inc_count): ?>
  <th id="checkheader" width="35" onclick="document.messages.checkAll.checked = !document.messages.checkAll.checked; makeSelection(-1);">
<?php if (!$print_view): ?>  
   <label for="checkAll" class="hidden">_("Check _All/None")</label>
   <input type="checkbox" class="checkbox" id="checkAll" name="checkAll" onclick="makeSelection(-1);" <?php echo Horde::getAccessKeyAndTitle(_("Check _All/None")) ?> />
<?php endif; ?>
  </th>
<?php else: ?>
  <th>&nbsp;</th>
<?php endif; ?>
<?php endif; ?>

<?php foreach ($headers as $key => $val): ?>
<?php if ($pageOb['mode'] == 'list'): ?>
  <th class="<?php echo ($sortby == $key) ? 'selected' : 'item' ?>" width="<?php echo $val['width'] ?>" onclick="document.location.href='<?php echo addslashes(Horde_Util::addParameter(($sortby == $key) ? $sorturl : $pageOb['url'], array('sortby' => $key, 'actionID' => 'change_sort'))) ?>';">
  <?php if ($sortby == $key): ?>
    <?php echo Horde::link(Horde_Util::addParameter($sorturl, array('sortby' => $key, 'actionID' => 'change_sort')), $val['stext'], null, null, null, $val['stext']) . Horde::img($sortImg, $sortText, '', $registry->getImageDir('horde')) ?></a>
  <?php endif; ?>
  <?php echo Horde::widget(Horde_Util::addParameter(($sortby == $key) ? $sorturl : $pageOb['url'], array('sortby' => $key, 'actionID' => 'change_sort')), $val['stext'], 'widget', null, null, $val['text']) ?>
<?php else: ?>
  <th class="item" width="<?php echo $val['width'] ?>">
  <?php echo str_replace('_', '', $val['text']) ?>
<?php endif; ?>
<?php if (isset($val['extra'])) echo $val['extra']; ?>
  </th>
<?php endforeach; ?>
 </tr>
