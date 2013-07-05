<?
unset($modulekit);
if(!isset($modulekit_default_includes))
  $modulekit_default_includes=
    array(
      'php'=>array("inc/*.php"),
      'js'=>array("inc/*.js"),
      'css'=>array("inc/*.css"),
    );

function modulekit_debug($text, $level) {
  global $modulekit_debug;

  if((!isset($modulekit_debug))||
     (!$modulekit_debug))
    return;

  if(is_callable($modulekit_debug))
    return call_user_func($modulekit_debug, $text, $level);

  if(($modulekit_debug===true)||($modulekit_debug>=$level))
    file_put_contents("php://stderr", "modulekit(L$level): $text\n");
}

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

function modulekit_include_js($include_index="js", $suffix="") {
  $ret="";

  if(!$include_index)
    $include_index="js";

  foreach(modulekit_get_includes("js") as $file) {
    $ret.="<script type='text/javascript' src=\"{$file}{$suffix}\"></script>\n";
  }

  return $ret;
}

function modulekit_include_css($include_index="css", $suffix="") {
  $ret="";

  if(!$include_index)
    $include_index="css";

  foreach(modulekit_get_includes("css") as $file) {
    $ret.="<link rel='stylesheet' type='text/css' href=\"{$file}{$suffix}\">\n";
  }

  return $ret;
}

function modulekit_load_module($module, $path, $default_include=null) {
  global $modulekit;

  modulekit_debug("Loading configuration for module '$module'", 2);

  if(file_exists("$path/modulekit.php"))
    require "$path/modulekit.php";

  // use all (newly) defined variables from modulekit.php
  $data=get_defined_vars();

  // remove all previously defined variables from $data, save "path"
  foreach(array("modulekit", "data", "module", "default_include") as $k)
    unset($data[$k]);

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

  $data['modules_path']="modules";
  if(isset($modules_path))
    $data['modules_path']=$modules_path;

  if(!isset($include)) {
    if($default_include!=null)
      $include=$default_include;
    else {
      global $modulekit_default_includes;
      $include=$modulekit_default_includes;
    }
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

  if(is_dir("{$path}/{$data['modules_path']}")) {
    $modules_dir=opendir("{$path}/{$data['modules_path']}/");
    while($module=readdir($modules_dir)) {
      if(substr($module, 0, 1)==".")
	continue;

      if(is_dir("{$path}/{$data['modules_path']}/{$module}"))
	modulekit_load_module($module, "{$path}/{$data['modules_path']}/$module", $default_include);
    }
  }

  return $data;
}

function modulekit_resolve_depend($module, &$done) {
  global $modulekit;
  $done[]=$module;

  if(!isset($modulekit['aliases'][$module]))
    throw new Exception("Can't resolve dependencies: '$module' not defined.");

  $data=$modulekit['modules'][$modulekit['aliases'][$module]];

  if(isset($data['depend'])&&is_array($data['depend']))
    foreach($data['depend'] as $m)
      if(!in_array($m, $done))
	modulekit_resolve_depend($m, $done);

  if(!in_array($data['id'], $modulekit['order']))
    $modulekit['order'][]=$data['id'];

  if(isset($data['load'])&&is_array($data['load']))
    foreach($data['load'] as $m)
      if(!in_array($m, $done))
        modulekit_resolve_depend($m, $done);
}

function modulekit_file($module, $path, $absolute_path=false) {
  global $modulekit;
  $prefix="";

  if($absolute_path)
    $prefix="{$modulekit['root_path']}/";

  return "{$prefix}{$modulekit['modules'][$modulekit['aliases'][$module]]['path']}/{$path}";
}

function modulekit_to_javascript() {
  global $modulekit;

  // TODO: remove maybe unwanted information?
  $ret ="<script type='text/javascript'>\n";
  $ret.="var modulekit=".json_encode($modulekit).";\n";
  $ret.="\n";
  $ret.=file_get_contents(dirname(__FILE__)."/loader.js");
  $ret.="</script>\n";

  return $ret;
}

function modulekit_includes($module, $type, $absolute_path=false) {
  global $modulekit;
  $prefix="";
  $list=array();

  if(isset($modulekit['modules'][$module]['include'][$type]))
    foreach($modulekit['modules'][$module]['include'][$type] as $f) {
      $list[]=modulekit_file($module, $f, $absolute_path);
    }

  return $list;
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

function modulekit_loaded($module) {
  global $modulekit;

  if(isset($modulekit['aliases'][$module]))
    $module=$modulekit['aliases'][$module];

  if(!in_array($module, $modulekit['order']))
    return false;

  return $modulekit['modules'][$module];
}

function modulekit_build_cache() {
  global $modulekit;

  if(!is_writeable(".modulekit-cache/"))
    return false;

  # Write variable to globals
  file_put_contents(".modulekit-cache/globals", serialize($modulekit));

  return true;
}

# No additional modules? Set to empty array
if(!isset($modulekit_load))
  $modulekit_load=array();

# If cache file is found then read configuration from there
if(file_exists(".modulekit-cache/globals")) {
  $modulekit=unserialize(file_get_contents(".modulekit-cache/globals"));
  $modulekit_is_cached=true;

  modulekit_debug("Loading configuration from cache", 1);

  # Check if list of modules-to-load has changed
  if(sizeof(array_diff($modulekit_load, $modulekit['load']))) {
    unset($modulekit);
    $modulekit_is_cached=false;
  }
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

  modulekit_debug("Loading configuration from modules", 1);

  modulekit_load($modulekit_load);

  $modulekit_is_cached=modulekit_build_cache();
}

# Include all include files
if((!isset($modulekit_no_include))||(!$modulekit_no_include)) {
  if(!isset($modulekit_include_php))
    $modulekit_include_php="php";

  foreach(modulekit_get_includes($modulekit_include_php) as $file) {
    modulekit_debug("Including {$modulekit_include_php} file  '$file'", 3);
    include_once($file);
  }
}
