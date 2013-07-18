function modulekit_loaded(module) {
  if((!modulekit)||
     (!modulekit.aliases))
    return false;

  if(modulekit.aliases[module])
    module=modulekit.aliases[module];

  for(var i=0; i<modulekit.order.length; i++)
    if(modulekit.order[i]==module)
      return modulekit.modules[module];

  return false;
}

function modulekit_file(module, path, absolute_path) {
  var prefix="";

  if(!path)
    path="";

  if(typeof absolute_path=="undefined")
    absolute_path=false;

  if(absolute_path===true) {
    var rel=modulekit_root_relative.split("../");
    prefix=location.pathname.split("/");
    prefix=prefix.slice(0, prefix.length-rel.length).join("/")+"/";
  }
  else if(absolute_path===false) {
    prefix=modulekit_root_relative;
  }

  if((!modulekit)||
     (!modulekit.modules))
     return;

  if((modulekit.aliases)&&
     (modulekit.aliases[module]))
    module=modulekit.aliases[module];

  if((!modulekit.modules[module])||
     (!modulekit.modules[module].path))
    return;

  return prefix+modulekit.modules[module].path+"/"+path;
}

function modulekit_get_includes(type) {
  var list=[];

  if((!modulekit)||
     (!modulekit.modules)||
     (!modulekit.order))
    return [];

  for(var i=0; i<modulekit.order.length; i++) {
    var m=modulekit.order[i];

    if(modulekit.modules[m].include&&modulekit.modules[m].include[type])
      for(var j=0; j<modulekit.modules[m].include[type].length; j++)
	list.push(modulekit_file(m, modulekit.modules[m].include[type][j]));
  }

  return list;
}
