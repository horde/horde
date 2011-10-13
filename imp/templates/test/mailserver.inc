<form name="form1" method="post" action="<?php $self_url ?>">
<table>
 <tr>
  <td align="right">
   <label for="server">Server:</label>
  </td>
  <td>
   <input type="text" id="server" name="server" />
  </td>
  <td>
   (If blank, attempts to connects to a server running on the same machine as IMP)
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="port">Port:</label>
  </td>
  <td>
   <input type="text" id="port" name="port" />
  </td>
  <td>
   (If non-standard port; leave blank to auto-detect using standard ports)
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="user">User:</label>
  </td>
  <td>
   <input type="text" id="user" name="user" />
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="passwd">Password:</label>
  </td>
  <td>
   <input type="password" id="passwd" name="passwd" />
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="server_type">Server Type:</label>
  </td>
  <td>
   <select id="server_type" name="server_type">
    <option value="imap">IMAP4rev1</option>
    <option value="pop">POP3</option>
   </select>
  </td>
 </tr>
 <tr>
  <td align="right">
   <label for="encrypt">Use SSL/TLS:</label>
  </td>
  <td>
   <select id="encrypt" name="encrypt">
    <option value="">No</option>
    <option value="ssl">SSL</option>
    <option value="tls">TLS</option>
   </select>
  </td>
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
