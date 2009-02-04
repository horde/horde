<tr class="text">
 <td nowrap="nowrap">
  <?php echo $found->getLink() ?>
 </td>
 <td class="nowrap"><?php echo htmlspecialchars($found->getLocation()) ?></td>
 <td class="nowrap"><?php echo Kronolith::statusToString($found->getStatus()) ?></td>
 <td class="nowrap"><?php echo $start->strftime($prefs->getValue('date_format')) . $start->strftime($prefs->getValue('twentyFour') ? ' %H:%M' : ' %I:%M %p') ?></td>
 <td class="nowrap"><?php echo $end->strftime($prefs->getValue('date_format')) . $end->strftime($prefs->getValue('twentyFour') ? ' %H:%M' : ' %I:%M %p') ?></td>
</tr>
