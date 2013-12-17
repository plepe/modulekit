<?
function modulekit_config_page() {
  global $modulekit;
  global $modulekit_load;
  $ret = "";

  $ret .= "<form method='post'>\n";
  foreach($modulekit['modules'] as $id=>$config) {
    $ret .= "<input type='checkbox' name='{$id}'";

    if(in_array($id, $modulekit_load))
      $ret .= " disabled='disabled'";

    if(in_array($id, $modulekit['load']))
      $ret .= " checked='checked'";

    $ret .= " /> {$id}<br>\n";
  }
  $ret .= "</form>\n";

  return $ret;
}
