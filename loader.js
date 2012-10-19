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

function modulekit_file(module, path) {
  if(!path)
    path="";

  if((!modulekit)||
     (!modulekit.modules))
     return;

  if((modulekit.aliases)&&
     (modulekit.aliases[module]))
    module=modulekit.aliases[module];

  if((!modulekit.modules[module])||
     (!modulekit.modules[module].path))
    return;

  return modulekit.modules[module].path+"/"+path;
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
