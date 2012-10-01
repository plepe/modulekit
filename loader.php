<?
unset($modulekit);
if(!isset($modulekit_default_includes))
  $modulekit_default_includes=
    array(
      'php'=>array("inc/*.php"),
      'js'=>array("inc/*.js"),
      'css'=>array("inc/*.css"),
    );

function modulekit_read_inc_files($basepath, $path="") {
  $list=array();

  if(!is_dir("{$basepath}/{$path}"))
    return array();

  $d=opendir("{$basepath}/{$path}");
  while($f=readdir($d)) {
    if(substr($f, 0, 1)==".");
    elseif(is_dir("{$basepath}/{$path}{$f}")) {
      $list=array_merge($list, modulekit_read_inc_files($basepath, "{$path}{$f}/"));
    }
    else {
      $list[]="{$path}{$f}";
    }
  }
  closedir($d);

  return $list;
}

function modulekit_files_match($files, $entry) {
  $entry="/^".strtr($entry, array(
    "."=>"\\.",
    "*"=>"[^\\/]*",
    "/"=>"\\/",
    "?"=>"[^\\/]",
  ))."$/";

  return preg_grep($entry, $files);
}

function modulekit_process_inc_files($basepath, $include) {
  $ret=array();

  $files=modulekit_read_inc_files($basepath, "");

  foreach($include as $type=>$list) {
    $f=array();

    foreach($list as $entry) {
      $f=array_merge($f, modulekit_files_match($files, $entry));
    }

    $ret[$type]=array_unique($f);
  }

  return $ret;
}

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

  if(file_exists("$path/modulekit.php"))
    require "$path/modulekit.php";

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

  if(!isset($include)) {
    global $modulekit_default_includes;
    $include=$modulekit_default_includes;
  }

  $data['include']=modulekit_process_inc_files($path, $include);

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

function modulekit_resolve_depend($module, &$done) {
  global $modulekit;
  $done[]=$module;

  $data=$modulekit['modules'][$modulekit['aliases'][$module]];

  if(isset($data['depend'])&&is_array($data['depend']))
    foreach($data['depend'] as $m)
      if(!in_array($m, $done))
	modulekit_resolve_depend($m, $done);

  $modulekit['order'][]=$data['id'];
}

function modulekit_file($module, $path, $absolute_path=false) {
  global $modulekit;
  $prefix="";

  if($absolute_path)
    $prefix="{$modulekit['root_path']}/";

  return "{$prefix}{$modulekit['modules'][$modulekit['aliases'][$module]]['path']}/{$path}";
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

function modulekit_load($additional) {
  modulekit_load_module("", ".");

  $resolve_done=array();
  modulekit_resolve_depend("", $resolve_done);
  foreach($additional as $add)
    modulekit_resolve_depend($add, $resolve_done);
}

# No additional modules? Set to empty array
if(!isset($modulekit_load))
  $modulekit_load=array();

# If cache file is found then read configuration from there
if(file_exists(".modulekit-cache/globals")) {
  $modulekit=unserialize(file_get_contents(".modulekit-cache/globals"));

  # Check if list of modules-to-load has changed
  print_r(array_diff($modulekit_load, $modulekit['load']));
  if(sizeof(array_diff($modulekit_load, $modulekit['load'])))
    unset($modulekit);
}

# No? Re-Build configuration
if(!isset($modulekit)) {
  $modulekit=array(
    'modules'	=>array(),
    'order'	=>array(),
    'aliases'	=>array(),
    'load'	=>$modulekit_load,
    'root_path'	=>dirname(dirname(__FILE__)),
  );

  modulekit_load($modulekit_load);
}

# Include all include files
foreach(modulekit_get_includes("php") as $file) {
  include_once($file);
}
