<?
function modulekit_config_page() {
  global $modulekit;
  global $modulekit_load;
  $ret = "";

  $ret .= "<form method='post'>\n";
  foreach($modulekit['modules'] as $id=>$config) {
    $ret .= "<div class='modulekit-config'>\n";

    $ret .= "<div class='checkbox'>\n";
    $ret .= "<input type='checkbox' name='{$id}'";

    if(in_array($id, $modulekit_load))
      $ret .= " disabled='disabled'";

    if(in_array($id, $modulekit['load']))
      $ret .= " checked='checked'";

    $ret .= " />";
    $ret .= "</div>";

    $ret .= "<div class='title'>\n";
    $ret .= array_key_exists('name', $config)?$config['name']:$id;
    $ret .= "</div>\n";

    if(array_key_exists('description', $config)) {
      $ret .= "<div class='description'>\n";
      $ret .= $config['description'];
      $ret .= "</div>\n";
    }

    $ret .="<div class='status'>\n";
    $ret .="Status: " . (in_array($id, $modulekit['order'])?"loaded":"not loaded")."\n";
    $ret .="</div>\n";

    $ret .= "</div>\n";
  }
  $ret .= "</form>\n";

  return $ret;
}
