<?php
class page_modules extends page {
  function check_filter($module_id, $module_def) {
    global $modulekit_page_config;

    if(!isset($modulekit_page_config))
      return true;

    if(!array_key_exists('filter', $modulekit_page_config))
      return true;

    return array_key_exists('category', $module_def) && ($module_def['category'] == $modulekit_page_config['filter']);
  }

// PAGE Main
  function content_main($param) {
    global $modulekit;

    $form_def = array(
      'load' => array(
	'type' => 'checkbox',
	'name' => 'Modules',
	'values' => array(),
      )
    );

    foreach($modulekit['modules'] as $module_id => $module_def) {
      if($this->check_filter($module_id, $module_def)) {
	$form_def['load']['values'][$module_id] = array(
	  'name' => array_key_exists('name', $module_def) ? $module_def['name'] : $module_id,
	  'desc' => array_key_exists('description', $module_def) ? $module_def['description'] : null
	);
      }
    }

    uasort($form_def['load']['values'], function($a, $b) {
      return strtolower($a['name']) < strtolower($b['name']) ? -1 : 1;
    });

    $form = new form("data", $form_def);

    if($form->is_complete()) {
      $modulekit['config'] = $form->save_data();
      
      if(modulekit_save_config() === true)
	messages_add("Modules list updated.", MSG_NOTICE);
      else
	messages_add("An error occured when updating module list.", MSG_NOTICE);

      reload();
    }

    if($form->is_empty()) {
      $form->set_data($modulekit['config']);
    }

    $ret  = "<form method='post'>\n";
    $ret .= $form->show();
    $ret .= "<input type='submit' value='Save'>\n";
    $ret .= "<a href='.?page=modules&amp;action=json'>JSON export/import</a>\n";
    $ret .= "</form>\n";

    return $ret;
  }

  function access_type_main($param) {
    return 'is_admin';
  }

// PAGE "json"
  function content_json() {
    global $modulekit;

    $ret=array();
    $form=new form("data", array(
      'json' => array(
        'type'=>'json',
	'name'=>"JSON",
      ),
    ));

    if($form->is_complete()) {
      $data = $form->save_data();
      $modulekit['config'] = $data['json'];

      if(modulekit_save_config() === true) {
	messages_add("Modules list updated.", MSG_NOTICE);
	reload();
      }
      else {
	messages_add("An error occured when updating module list.", MSG_ERROR);
      }
    }

    if($form->is_empty()) {
      $form->set_data(array('json' => $modulekit['config']));
    }

    $ret  ="<form method='post'>\n";
    $ret .=$form->show();
    $ret .="<input type='submit' value='Save'>\n";
    $ret .= "<a href='.?page=modules'>Normal view</a>\n";
    $ret .= "</form>\n";

    return $ret;
  }

  function access_type_json() {
    return "is_admin";
  }
}
