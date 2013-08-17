<?php

  class ctrl_sold_out_status {
    public $data = array();
    
    public function __construct($sold_out_status_id=null) {
      
      if ($sold_out_status_id !== null) $this->load($sold_out_status_id);
    }
    
    public function load($sold_out_status_id) {
      $sold_out_status_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_SOLD_OUT_STATUSES ."
        where id = '". (int)$sold_out_status_id ."'
        limit 1;"
      );
      $this->data = $GLOBALS['system']->database->fetch($sold_out_status_query);
      if (empty($this->data)) trigger_error('Could not find sold out status ID ('. $sold_out_status_id .') in database.', E_USER_ERROR);
      
      $sold_out_status_info_query = $GLOBALS['system']->database->query(
        "select name, description, language_code from ". DB_TABLE_SOLD_OUT_STATUSES_INFO ."
        where sold_out_status_id = '". (int)$this->data['id'] ."';"
      );
      while ($sold_out_status_info = $GLOBALS['system']->database->fetch($sold_out_status_info_query)) {
        foreach ($sold_out_status_info as $key => $value) {
          $this->data[$key][$sold_out_status_info['language_code']] = $value;
        }
      }
    }
    
    public function save() {
    
      if (empty($this->data['id'])) {
        $GLOBALS['system']->database->query(
          "insert into ". DB_TABLE_SOLD_OUT_STATUSES ."
          (date_created)
          values ('". $GLOBALS['system']->database->input(date('Y-m-d H:i:s')) ."');"
        );
        $this->data['id'] = $GLOBALS['system']->database->insert_id();
      }
      
      $GLOBALS['system']->database->query(
        "update ". DB_TABLE_SOLD_OUT_STATUSES ."
        set orderable = '". (empty($this->data['orderable']) ? 0 : 1) ."',
          date_updated = '". date('Y-m-d H:i:s') ."'
        where id = '". (int)$this->data['id'] ."'
        limit 1;"
      );
      
      foreach (array_keys($GLOBALS['system']->language->languages) as $language_code) {
        
        $sold_out_status_info_query = $GLOBALS['system']->database->query(
          "select * from ". DB_TABLE_SOLD_OUT_STATUSES_INFO ."
          where sold_out_status_id = '". (int)$this->data['id'] ."'
          and language_code = '". $language_code ."'
          limit 1;"
        );
        $sold_out_status_info = $GLOBALS['system']->database->fetch($sold_out_status_info_query);
        
        if (empty($sold_out_status_info['id'])) {
          $GLOBALS['system']->database->query(
            "insert into ". DB_TABLE_SOLD_OUT_STATUSES_INFO ."
            (sold_out_status_id, language_code)
            values ('". (int)$this->data['id'] ."', '". $language_code ."');"
          );
          $sold_out_status_info['id'] = $GLOBALS['system']->database->insert_id();
        }
        
        $GLOBALS['system']->database->query(
          "update ". DB_TABLE_SOLD_OUT_STATUSES_INFO ."
          set
            name = '". $GLOBALS['system']->database->input($this->data['name'][$language_code]) ."',
            description = '". $GLOBALS['system']->database->input($this->data['description'][$language_code]) ."'
          where id = '". (int)$sold_out_status_info['id'] ."'
          and sold_out_status_id = '". (int)$this->data['id'] ."'
          and language_code = '". $language_code ."'
          limit 1;"
        );
      }
      
      $GLOBALS['system']->cache->set_breakpoint();
    }
    
    public function delete() {
    
      if ($GLOBALS['system']->database->num_rows($GLOBALS['system']->database->query("select id from ". DB_TABLE_PRODUCTS ." where sold_out_status_id = '". (int)$this->data['id'] ."' limit 1;"))) {
        trigger_error('Cannot delete the sold out status because there are products using it', E_USER_ERROR);
        return;
      }
      
      $GLOBALS['system']->database->query(
        "delete from ". DB_TABLE_SOLD_OUT_STATUSES_INFO ."
        where sold_out_status_id = '". (int)$this->data['id'] ."';"
      );
      
      $GLOBALS['system']->database->query(
        "delete from ". DB_TABLE_SOLD_OUT_STATUSES ."
        where id = '". (int)$this->data['id'] ."'
        limit 1;"
      );
      
      $this->data['id'] = null;
      
      $GLOBALS['system']->cache->set_breakpoint();
    }
  }

?>