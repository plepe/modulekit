function modulekit_loaded(module) {
  if((!modulekit)||
     (!modulekit.aliases))
    return false;

  if(modulekit.aliases[module])
    module=modulekit.aliases[module];

  for(var i=0; i<modulekit.load.length; i++)
    if(modulekit.load[i]==module)
      return true;

  return false;
}

function modulekit_file(module, path) {
  if(!path)
    path="";

  if((!modulekit)||
     (!modulekit.modules)||
     (!modulekit.modules[module])||
     (!modulekit.modules[module].path))
     return;

  return modulekit.modules[module].path+"/"+path;
}
