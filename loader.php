<?
$modulekit=array(
  'modules'	=>array(),
  'order'	=>array(),
  'aliases'	=>array(),
);

function modulekit_include_js($suffix="") {
  $ret="";

  foreach(modulekit_get_includes("js") as $file) {
    $ret.="<script type='text/javascript' src=\"{$file}{$suffix}\"></script>\n";
  }

  return $ret;
}

function modulekit_include_css($suffix="") {
  $ret="";

  foreach(modulekit_get_includes("css") as $file) {
    $ret.="<link rel='stylesheet' type='text/css' href=\"{$file}{$suffix}\">\n";
  }

  return $ret;
}

function modulekit_load_module($module, $path) {
  global $modulekit;

  $data=array(
    'path'=>$path
  );

  @include "$path/modulekit.php";

  $modulekit['aliases'][$module]=$module;

  if(isset($name))
    $data['name']=$name;

  if(isset($id)) {
    $modulekit['aliases'][$module]=$id;
    $module=$id;
  }
  $data['id']=$module;
  $modulekit['aliases'][$module]=$module;

  if(isset($depend))
    $data['depend']=$depend;

  if(!isset($include))
    $include=array();
  $data['include']=$include;

  // compatibility
  if(isset($include_php))
    $data['include']['php']=$include_php;
  if(isset($include_js))
    $data['include']['js']=$include_js;
  if(isset($include_css))
    $data['include']['css']=$include_css;

  $modulekit['modules'][$module]=$data;

  if(is_dir("{$path}/modules")) {
    $modules_dir=opendir("{$path}/modules/");
    while($module=readdir($modules_dir)) {
      if(substr($module, 0, 1)==".")
	continue;

      if(is_dir("{$path}/modules/{$module}"))
	modulekit_load_module($module, "{$path}/modules/$module");
    }
  }

  return $data;
}

function modulekit_resolve_depend($module, $done=array()) {
  global $modulekit;
  $done[]=$module;

  $data=$modulekit['modules'][$modulekit['aliases'][$module]];

  if(isset($data['depend'])&&is_array($data['depend']))
    foreach($data['depend'] as $m)
      if(!in_array($m, $done))
	modulekit_resolve_depend($m, &$done);

  $modulekit['order'][]=$data['id'];
}

function modulekit_file($module, $path) {
  global $modulekit;

  return "{$modulekit['modules'][$modulekit['aliases'][$module]]['path']}/{$path}";
}

function modulekit_get_includes($type) {
  global $modulekit;
  $list=array();

  foreach($modulekit['order'] as $m) {
    if(isset($modulekit['modules'][$m]['include'][$type]))
      foreach($modulekit['modules'][$m]['include'][$type] as $f) {
	$list[]=modulekit_file($m, $f);
      }
  }

  return $list;
}

function modulekit_build_include_list($type) {
  trigger_error("modulekit_build_include_list() deprecated, use modulekit_get_includes() instead", E_USER_DEPRECATED);
  return modulekit_get_includes($type);
}

function modulekit_load() {
  modulekit_load_module("", ".");

  modulekit_resolve_depend("");
}

# If cache file is found then read configuration from there
if(file_exists(".modulekit-cache/globals")) {
  $modulekit=unserialize(file_get_contents(".modulekit-cache/globals"));
}
# No? Re-Build configuration
else {
  modulekit_load();
}

# Include all include files
foreach(modulekit_get_includes("php") as $file) {
  include_once($file);
}
