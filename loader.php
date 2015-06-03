<?php
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
  global $modulekit_root;

  $list=array();

  if(!@is_dir("{$modulekit_root}{$basepath}/{$path}"))
    return array();

  $d=opendir("{$modulekit_root}{$basepath}/{$path}");
  while($f=readdir($d)) {
    if(substr($f, 0, 1)==".");
    elseif(@is_dir("{$modulekit_root}{$basepath}/{$path}{$f}")) {
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
    "+"=>"\\+",
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

function modulekit_include_js($include_index="js", $suffix=null) {
  global $modulekit;
  global $modulekit_cache_dir;
  global $modulekit_root_relative;
  $ret="";

  if($suffix==null)
    $suffix="?{$modulekit['version']}";

  if(!$include_index)
    $include_index="js";

  if(  isset($modulekit['compiled'])
     &&isset($modulekit['compiled'][$include_index])) {
    $ret.="<script type='text/javascript' src=\"{$modulekit_cache_dir}{$modulekit['compiled'][$include_index]}{$suffix}\"></script>\n";

  }
  else {
    foreach(modulekit_get_includes($include_index) as $file) {
      $ret.="<script type='text/javascript' src=\"{$file}{$suffix}\"></script>\n";
    }
  }

  return $ret;
}

function modulekit_include_css($include_index="css", $suffix=null) {
  global $modulekit;
  global $modulekit_cache_dir;
  global $modulekit_root_relative;
  $ret="";

  if($suffix==null)
    $suffix="?{$modulekit['version']}";

  if(!$include_index)
    $include_index="css";

  if(  isset($modulekit['compiled'])
     &&isset($modulekit['compiled'][$include_index])) {
    $ret.="<link rel='stylesheet' type='text/css' href=\"{$modulekit_cache_dir}{$modulekit['compiled'][$include_index]}{$suffix}\">\n";

  }
  else {
    foreach(modulekit_get_includes($include_index) as $file) {
      $ret.="<link rel='stylesheet' type='text/css' href=\"{$file}{$suffix}\">\n";
    }
  }

  return $ret;
}

function modulekit_version_build($module, $path) {
  global $modulekit_root;

  if(file_exists("{$modulekit_root}{$path}/.git")) {
    $version_build=shell_exec("cd \"{$modulekit_root}{$path}\"; if [ \"`which git`\" != \"\" ] ; then echo `git rev-parse --short HEAD` ; fi 2> /dev/null");
    return "git.".trim($version_build);
  }

  return null;
}

function modulekit_module_is_empty($module, $path) {
  global $modulekit_root;

  $count=0;

  $d=opendir("{$modulekit_root}{$path}");
  while($f=readdir($d))
    if(substr($f, 0, 1)!=".")
      $count++;

  if($count==0)
    return true;

  return false;
}

function modulekit_load_module($module, $path, $parent=array()) {
  global $modulekit;
  global $modulekit_root;

  modulekit_debug("Loading configuration for module '$module'", 2);

  if(file_exists("{$modulekit_root}{$path}modulekit.php"))
    require "{$modulekit_root}{$path}modulekit.php";
  // ignore empty directories
  else
    if(modulekit_module_is_empty($module, $path))
      return;

  // use all (newly) defined variables from modulekit.php
  $data=get_defined_vars();

  // remove all previously defined variables from $data, save "path"
  foreach(array("modulekit", "modulekit_root", "data", "module", "parent") as $k)
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

  $data['version_build']=modulekit_version_build($module, $path);
  if($data['version_build']===null)
    $data['version_build']=$parent['version_build'];

  if(!isset($data['version'])) {
    if(isset($parent['version'])) {
      if($data['version_build']==$parent['version_build'])
	$data['version']=$parent['version'];
      else
	$data['version']="";
    }
    else {
      global $version;

      if(isset($version))
	$data['version']=$version;
      else
	$data['version']="";
    }
  }

  if(isset($depend))
    $data['depend']=$depend;

  if(!array_key_exists('modules_path', $data))
    $data['modules_path']="modules";

  if(!isset($include)) {
    if((array_key_exists('default_include', $parent) && ($parent['default_include']!=null)))
      $include=$parent['default_include'];
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

  if(($data['modules_path']!==null)&&
     @is_dir("{$modulekit_root}{$path}{$data['modules_path']}")) {
    if($data['modules_path']==".")
      $modules_dir_path=$path;
    else
      $modules_dir_path="{$path}{$data['modules_path']}/";
    $modules_dir=opendir("{$modulekit_root}{$modules_dir_path}");

    while($module=readdir($modules_dir)) {
      if(substr($module, 0, 1)==".")
	continue;

      if(@is_dir("{$modulekit_root}{$modules_dir_path}{$module}/"))
	modulekit_load_module($module, "{$modules_dir_path}{$module}/", $data);
    }

    closedir($modules_dir);
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
    foreach($data['depend'] as $m=>$version_constraint) {
      if(is_integer($m)) {
	$m=$version_constraint;
	$version_constraint=null;
      }

      if(!in_array($m, $done))
	modulekit_resolve_depend($m, $done);

      $check_version=modulekit_check_version($modulekit['modules'][$modulekit['aliases'][$m]]['version'], $version_constraint);
      if($check_version!==true) {
	throw new Exception("Can't resolve dependencies: {$check_version} of module '$m'");
      }
    }

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

  if($absolute_path===true) {
    global $modulekit_root;
    $prefix=$modulekit_root;
  }
  elseif($absolute_path===false) {
    global $modulekit_root_relative;
    $prefix=$modulekit_root_relative;
  }

  return "{$prefix}{$modulekit['modules'][$modulekit['aliases'][$module]]['path']}{$path}";
}

function modulekit_to_javascript() {
  global $modulekit;
  global $modulekit_root_relative;

  // TODO: remove maybe unwanted information?
  $ret ="<script type='text/javascript'>\n";
  $ret.="var modulekit=".json_encode($modulekit).";\n";
  $ret.="var modulekit_root_relative=".json_encode($modulekit_root_relative).";\n";
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
  modulekit_load_module("", "");
  modulekit_load_module("modulekit", "modulekit/");

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

function modulekit_pack_include_files($type, $mode=null) {
  global $modulekit;
  global $modulekit_cache_dir;

  if(!$mode)
    $mode=$type;

  // Open cache file
  $filename="compiled_{$type}.{$mode}";
  @unlink("{$modulekit_cache_dir}{$filename}");
  $f=fopen("{$modulekit_cache_dir}{$filename}", "w");

  // Concatenate all Javascript files into one
  foreach(modulekit_get_includes($type) as $file) {
    // beginning of file
    switch($mode) {
      case "php":
        fwrite($f, "<"."?php /* FILE {$file} */ ?".">\n");
	break;
      case "js":
      case "css":
        fwrite($f, "/* FILE {$file} */\n");
	break;
      default:
    }

    // content of file
    fwrite($f, $content=file_get_contents($file));

    // end of file
    switch($mode) {
      case "php":
	// make sure every file ends with a php closing tag
	$last_begin_tag=strrpos($content, "<"."?");
	$last_end_tag=strrpos($content, "?".">");
	if(($last_begin_tag!==false)&&
	  (($last_end_tag===false)||($last_end_tag<$last_begin_tag)))
	  fwrite($f, "?".">\n");
        break;
      default:
    }
  }

  // Done writing file
  fclose($f);

  // register file
  $modulekit['compiled'][$type]=$filename;
}

function modulekit_build_cache() {
  global $modulekit;
  global $modulekit_cache_dir;

  if(!is_writeable("{$modulekit_cache_dir}"))
    return false;

  // Concatenate files into one
  modulekit_pack_include_files("js");
  modulekit_pack_include_files("php");
  modulekit_pack_include_files("css");

  # Write variable to globals
  file_put_contents("{$modulekit_cache_dir}globals", serialize($modulekit));

  return true;
}

function modulekit_get_root_modulekit_version() {
  global $modulekit_root;

  include "{$modulekit_root}modulekit.php";
  $ret="";

  $version_build=modulekit_version_build("", ".");

  if(isset($version)) {
    $ret.=$version;
  }
  else {
    global $version;

    if(isset($version))
      $ret.=$version;
  }

  if($version_build)
    $ret.="+{$version_build}";

  return $ret;
}

// You may define a $version in conf.php, e.g. "2.0.1"
// If the current program is a git repository, the short SHA-1 of the current
// HEAD will be appended as build metadata, e.g. "2.0.1+git.7bd9274"
function modulekit_version() {
  $modulekit_version="";
  $data=modulekit_loaded("");

  $modulekit_version.=$data['version'];

  if($data['version_build'])
    $modulekit_version.="+{$data['version_build']}";

  return $modulekit_version;
}

function modulekit_check_version($version, $constraint) {
  if(!$constraint)
    return true;

  if(!$version)
    return "no version defined";

  if(preg_match("/^([0-9\.]+)[-+]/", $version, $m))
    $version=$m[1];

  $version_parts=explode(".", $version);
  $constraint_parts=explode(".", $constraint);

  $is_higher="requires version {$constraint}";

  foreach($constraint_parts as $i=>$constraint_part) {
    if(sizeof($version_parts)<=$i) {
      return $is_higher;
    }

    if((int)$version_parts[$i]>(int)$constraint_part)
      $is_higher=true;
    if((int)$version_parts[$i]<(int)$constraint_part)
      return "requires version {$constraint}";
  }

  return true;
}

function modulekit_cache_invalid() {
  global $modulekit;
  global $modulekit_load;

  if(sizeof(array_diff(array_merge($modulekit_load, $modulekit['config']['load']), $modulekit['load'])))
    return true;

  if(modulekit_get_root_modulekit_version()!=$modulekit['version'])
    return true;

  return false;
}

function modulekit_clear_cache() {
  global $modulekit_cache_dir;

  # Empty cache directory
  $d=opendir($modulekit_cache_dir);
  while($f=readdir($d)) {
    if(substr($f, 0, 1)!=".")
      @unlink("{$modulekit_cache_dir}/$f");
  }
  closedir($d);
}

function modulekit_load_config() {
  global $modulekit;
  $file = dirname(__FILE__)."/config";

  $modulekit['config'] = array();
  if(file_exists($file)) {
    $modulekit['config'] = @json_decode(file_get_contents($file), true);

    if(!$modulekit['config'])
      $modulekit['config'] = array();
  }

  if(!array_key_exists('load', $modulekit['config']))
    $modulekit['config']['load'] = array();

  return $modulekit['config'];
}

function modulekit_save_config() {
  global $modulekit;
  $file = dirname(__FILE__)."/config";

  $r = file_put_contents($file, json_encode($modulekit['config']));

  if($r === false)
    return false;

  return true;
}

function modulekit_config_writable() {
  global $modulekit;
  $file = dirname(__FILE__)."/config";

  return is_writable($file);
}

# No additional modules? Set to empty array
if(!isset($modulekit_load))
  $modulekit_load=array();

if(!isset($modulekit_nocache))
  $modulekit_nocache=false;

# Get absolute and relative path to root of repository
$modulekit_root=dirname(dirname(__FILE__))."/";
$modulekit_root_relative="";

if(substr(getcwd(), 0, strlen($modulekit_root))==$modulekit_root) {
  $rel=explode("/", substr(getcwd(), strlen($modulekit_root)));
  $modulekit_root_relative=str_repeat("../", sizeof($rel));
}
elseif(getcwd()."/"==$modulekit_root) {
  $modulekit_root_relative="";
}
elseif(substr($modulekit_root, 0, strlen(getcwd()))==getcwd()) {
  $rel=explode("/", substr($modulekit_root, strlen(getcwd())));
  $modulekit_root_relative=implode("/", array_slice($rel, 1));
}
else {
  $modulekit_root_relative=$modulekit_root;
}

# Check location of (possible) modulekit cache directory
if(isset($modulekit_cache_dir)) {
  $modulekit_cache_dir="{$modulekit_root_relative}{$modulekit_cache_dir}";
}
else
  $modulekit_cache_dir="{$modulekit_root_relative}.modulekit-cache/";

# If cache file is found then read configuration from there
if((!$modulekit_nocache)&&(file_exists("{$modulekit_cache_dir}globals"))) {
  $modulekit=unserialize(file_get_contents("{$modulekit_cache_dir}globals"));
  $modulekit_is_cached=true;

  modulekit_debug("Loading configuration from cache", 1);

  modulekit_load_config();

  # Check if list of modules-to-load has changed
  if(modulekit_cache_invalid()) {
    unset($modulekit);
    $modulekit_is_cached=false;

    modulekit_clear_cache();
  }
}

# No? Re-Build configuration
if(!isset($modulekit)) {
  modulekit_load_config();

  $modulekit=array(
    'modules'	=>array(),
    'order'	=>array(),
    'aliases'	=>array(),
    'load'	=>array_merge($modulekit_load, $modulekit['config']['load']),
    'config'    =>$modulekit['config'],
    'root_path'	=>$modulekit_root,
  );

  modulekit_debug("Loading configuration from modules", 1);

  modulekit_load($modulekit['load']);

  $modulekit['version']=modulekit_version();

  if(!$modulekit_nocache)
    $modulekit_is_cached=modulekit_build_cache();
}

# Include all include files
if((!isset($modulekit_no_include))||(!$modulekit_no_include)) {
  if(!isset($modulekit_include_php))
    $modulekit_include_php="php";

  if(  isset($modulekit['compiled'])
     &&isset($modulekit['compiled'][$modulekit_include_php])) {
    include_once("{$modulekit_cache_dir}{$modulekit['compiled'][$modulekit_include_php]}");
  }
  else {
    foreach(modulekit_get_includes($modulekit_include_php) as $file) {
      modulekit_debug("Including {$modulekit_include_php} file  '$file'", 3);
      include_once($file);
    }
  }
}
