<?php foreach($postings as $postingId => $posting): ?>
<?php $rowId++; ?>
 <tr id="row<?php echo $rowId ?>" class="<?php if (!$print_view) { echo ($posting['eo'] ? 'eo' : '') . $posting['account_type']; } ?>">
  <td><?php if (!$print_view) { ?><input id="check<?php echo $rowId ?>" type="checkbox" class="checkbox" name="indices[]" onclick="selectRow(<?php echo $rowId ?>);" value="<?php echo $postingId ?>"<?php if ($closedperiod >= $posting['date']) echo ' disabled="disabled"' ?> /><?php } echo Horde::img(($posting['eo'] ? 'eo' : '') . $posting['account_type'] . '.png', $posting['account_type_eo'], array('title' => $posting['account_type_eo'])) ?></td>
  <td class="ohide"><?php echo $overflow_begin, strftime($datefmt, $posting['date']), $overflow_end ?></td>
  <td class="ohide <?php echo $posting['asset_closed'] ? 'closed' : ''?>" title="<?php echo strlen($posting['asset_label']) > 25 ? $posting['asset_label'] : '' ?>"><?php echo $overflow_begin, $posting['asset_label'], $overflow_end ?></td>
  <td class="ohide <?php echo $posting['account_closed'] ? 'closed' : ''?>" title="<?php echo strlen($posting['account_label']) > 25 ? $posting['account_label'] : '' ?>"><?php echo $overflow_begin, $posting['account_label'], $overflow_end ?></td>
  <td class="ohide" title="<?php echo strlen($posting['desc']) > 25 ? $posting['desc'] : '' ?>"><?php echo $overflow_begin, $posting['desc'], $overflow_end ?></td>
  <td class="<?php echo ($posting['amount'] < 0) ? 'negative' : 'positive' ?> amount"><?php echo $overflow_begin, Fima::convertValueToAmount($posting['amount']), $overflow_end ?></td>
 </tr>
<?php endforeach; ?>
 <tr class="postingInfo">
  <td colspan="5" class="rightAlign"><?php echo _("Total Result") ?>:</td>
  <td class="<?php echo (Fima::getPostingsResult() < 0) ? 'negative' : 'positive' ?> amount"><?php echo Fima::convertValueToAmount(Fima::getPostingsResult()) ?></td>
 </tr>
