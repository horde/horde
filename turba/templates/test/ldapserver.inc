<form name="form1" method="post" action="<?php echo $self_url ?>">
<table>
 <tr>
  <td align="right">
   <label for="server">Server</label>
  </td>
  <td>
   <input type="text" id="server" name="server" />
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="port">Port</label>
  </td>
  <td>
   <input type="text" id="port" name="port" />
  </td>
  <td>(defaults to "389")</td>
 </tr>
 <tr>
  <td align="right">
   <label for="basedn">Base DN</label>
  </td>
  <td>
   <input type="text" id="basedn" name="basedn" />
  </td>
  <td>(e.g. "dc=example,dc=com")</td>
 </tr>
 <tr>
  <td align="right">
   <label for="user">User</label>
  </td>
  <td>
   <input type="text" id="user" name="user" />
  </td>
  <td>(leave blank for anonymous)</td>
 </tr>
 <tr>
  <td align="right">
   <label for="passwd">Password</label>
  </td>
  <td>
   <input type="password" id="passwd" name="passwd" />
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="filter">Filter</label>
  </td>
  <td>
   <input type="text" id="filter" name="filter" />
  </td>
  <td>(e.g. "cn=Babs Jensen")</td>
 </tr>
 <tr>
  <td align="right">
   <label for="proto">Protocol</label>
  </td>
  <td>
   <select id="version" name="version">
    <option value="2">LDAPv2 (Deprecated)</option>
    <option value="3" selected="selected">LDAPv3</option>
   </select>
  </td>
  <td>(LDAP protocol version)</td>
 </tr>
 <tr>
  <td></td>
  <td>
   <input type="submit" name="f_submit" value="Submit" />
   <input type="reset" name="f_reset" value="Reset" />
  </td>
 </tr>
</table>
</form>
