<tr>
<td><?php echo Horde::link(Horde_Util::addParameter($url, 'id', $row['id']), $row['title']) . $row['id'] . '</a>'; ?></td>
<td><?php echo Horde::link(Horde_Util::addParameter($url, 'actionID', $row['user']), $row['user']) . $row['user'] . '</a>'; ?></td>
<td><?php echo $row['ip'] ?></td>
<td><?php echo $row['readdate'] ?></td>
</tr>
