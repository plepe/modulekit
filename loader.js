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
