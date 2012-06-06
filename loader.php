<?
$modulekit_modules=array();
$modulekit_order=array();

function modulekit_include_js() {
}

function modulekit_include_css() {
}

function modulekit_load_module($module, $path) {
  global $modulekit_modules;
  $data=array(
    'path'=>$path
  );

  @include "$path/modulekit.php";

  if(isset($name))
    $data['name']=$name;
  if(isset($depend))
    $data['depend']=$depend;
  if(isset($include_php))
    $data['include_php']=$include_php;
  if(isset($include_js))
    $data['include_js']=$include_js;
  if(isset($include_css))
    $data['include_css']=$include_css;

  $modulekit_modules[$module]=$data;
  return $data;
}

function modulekit_resolve_depend($module, $done=array()) {
  global $modulekit_modules;
  global $modulekit_order;
  $done[]=$module;

  $data=$modulekit_modules[$module];

  if(isset($data['depend'])&&is_array($data['depend']))
    foreach($data['depend'] as $m)
      if(!in_array($m, $done))
	modulekit_resolve_depend($m, &$done);

  $modulekit_order[]=$module;
}

function modulekit_file($module, $path) {
  global $modulekit_modules;

  return "{$modulekit_modules[$module]['path']}/{$path}";
}

function modulekit_build_include_php() {
  global $modulekit_order;
  global $modulekit_modules;
  $list=array();

  foreach($modulekit_order as $m) {
    if(isset($modulekit_modules[$m]['include_php']))
      foreach($modulekit_modules[$m]['include_php'] as $f) {
	$list[]=modulekit_file($m, $f);
      }
  }

  return $list;
}

function modulekit_load() {
  modulekit_load_module("", ".");

  $modules_dir=opendir("modules/");
  while($module=readdir($modules_dir)) {
    if(substr($module, 0, 1)==".")
      continue;

    modulekit_load_module($module, "modules/$module");
  }

  modulekit_resolve_depend("");
  return modulekit_build_include_php();
}

foreach(modulekit_load() as $file) {
  include $file;
}
