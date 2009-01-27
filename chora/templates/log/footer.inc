</tbody>
</table>

<?php if ($fl->revisionCount() > 100 && !Util::getFormData('all')): ?>
<table class="options" cellspacing="0">
 <tr>
  <td>
   <?php printf(_("Only showing the 100 latest revisions. %sShow all revisions?</a> (may take a while)."),
                Horde::link(Util::addParameter(Horde::selfUrl(true), 'all', 1))) ?>
  </td>
 </tr>
</table>
<?php endif ?>
