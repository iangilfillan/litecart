<?php
  
  class ctrl_order {
    public $data;
    
    public function __construct($action='new', $order_id='') {
      
      if (!isset($GLOBALS['system']->session->data['order'])) $GLOBALS['system']->session->data['order'] = array();
      $this->data = &$GLOBALS['system']->session->data['order'];
      
      switch ($action) {
        case 'load':
          if (empty($order_id)) trigger_error('Unknown order id', E_USER_ERROR);
          $this->load($order_id);
          break;
        case 'new':
          $this->reset();
          break;
        case 'import_session':
          $this->import_session();
          break;
        case 'resume':
        default:
          break;
      }
    }
    
    public function reset() {
      
      $this->data = array(
        'id' => '',
        'uid' => uniqid(),
        'items' => array(),
        'weight' => 0,
        'weight_class' => $GLOBALS['system']->settings->get('store_weight_class'),
        'currency_code' => $GLOBALS['system']->currency->selected['code'],
        'currency_value' => $GLOBALS['system']->currency->selected['value'],
        'language_code' => $GLOBALS['system']->language->selected['code'],
        'customer' => array(
          'id' => '',
          'email' => '',
          'desired_password' => '',
          'phone' => '',
          'tax_id' => '',
          'company' => '',
          'firstname' => '',
          'lastname' => '',
          'address1' => '',
          'address2' => '',
          'city' => '',
          'postcode' => '',
          'country_code' => '',
          'zone_code' => '',
          'shipping_address' => array(
            'company' => '',
            'firstname' => '',
            'lastname' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'postcode' => '',
            'country_code' => '',
            'zone_code' => '',
          ),
        ),
        'shipping_option' => array(),
        'payment_tracking_id' => '',
        'payment_option' => array(),
        'payment_transaction_id' => '',
        'order_total' => array(),
        'tax_total' => 0,
        'weight_total' => 0,
        'weight_class' => $GLOBALS['system']->settings->get('store_weight_class'),
        'payment_due' => 0,
        'order_status_id' => 0,
        'comments' => array(),
      );
    }
    
    private function import_session() {
      global $shipping, $payment, $order_total;
      
      $this->reset();
      
      $this->data['weight_class'] = $GLOBALS['system']->settings->get('store_weight_class');
      $this->data['currency_code'] = $GLOBALS['system']->currency->selected['code'];
      $this->data['currency_value'] = $GLOBALS['system']->currency->currencies[$GLOBALS['system']->currency->selected['code']]['value'];
      $this->data['language_code'] = $GLOBALS['system']->language->selected['code'];
      
      $this->data['customer'] = $GLOBALS['system']->customer->data;
      
      if (!empty($shipping->data['selected'])) {
        $this->data['shipping_option'] = array(
          'id' => $shipping->data['selected']['id'],
          'name' => $shipping->data['selected']['title'] .' ('. $shipping->data['selected']['name'] .')',
        );
      }
      
      if (!empty($payment->data['selected'])) {
        $this->data['payment_option'] = array(
          'id' => $payment->data['selected']['id'],
          'name' => $payment->data['selected']['title'] .' ('. $payment->data['selected']['name'] .')',
        );
      }
      
      foreach ($GLOBALS['system']->cart->data['items'] as $item) {
        $this->add_item($item);
      }
      
      foreach ($order_total->rows as $row) {
        $this->add_ot_row($row);
      }
    }
    
    private function load($order_id) {
      
      $this->reset();
      
      $order_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_ORDERS ."
        where id = '". (int)$order_id ."'
        limit 1;"
      );
      $order = $GLOBALS['system']->database->fetch($order_query);
      if (empty($order)) trigger_error('Could not find order in database ('. $order_id .')', E_USER_ERROR);
      
      $key_map = array(
        'id' => 'id',
        'weight_total' => 'weight_total',
        'weight_class' => 'weight_class',
        'currency_code' => 'currency_code',
        'currency_value' => 'currency_value',
        'language_code' => 'language_code',
        'payment_due' => 'payment_due',
        'order_status_id' => 'order_status_id',
        'shipping_tracking_id' => 'shipping_tracking_id',
        'payment_transaction_id' => 'payment_transaction_id',
      );
      foreach ($key_map as $skey => $tkey){
        $this->data[$tkey] = $order[$skey];
      }
      
      $key_map = array(
        'customer_id' => 'id',
        'customer_email' => 'email',
        'customer_tax_id' => 'tax_id',
        'customer_company' => 'company',
        'customer_firstname' => 'firstname',
        'customer_lastname' => 'lastname',
        'customer_address1' => 'address1',
        'customer_address2' => 'address2',
        'customer_postcode' => 'postcode',
        'customer_city' => 'city',
        'customer_phone' => 'phone',
        'customer_mobile' => 'mobile',
        'customer_country_code' => 'country_code',
        'customer_zone_code' => 'zone_code',
      );
      foreach ($key_map as $skey => $tkey){
        $this->data['customer'][$tkey] = $order[$skey];
      }
      
      $key_map = array(
        'shipping_company' => 'company',
        'shipping_firstname' => 'firstname',
        'shipping_lastname' => 'lastname',
        'shipping_address1' => 'address1',
        'shipping_address2' => 'address2',
        'shipping_postcode' => 'postcode',
        'shipping_city' => 'city',
        'shipping_country_code' => 'country_code',
        'shipping_zone_code' => 'zone_code',
      );
      foreach ($key_map as $skey => $tkey){
        $this->data['customer']['shipping_address'][$tkey] = $order[$skey];
      }
      
      $key_map = array(
        'shipping_option_id' => 'id',
        'shipping_option_name' => 'name',
      );
      foreach ($key_map as $skey => $tkey){
        $this->data['shipping_option'][$tkey] = $order[$skey];
      }
      
      $key_map = array(
        'payment_option_id' => 'id',
        'payment_option_name' => 'name',
      );
      foreach ($key_map as $skey => $tkey){
        $this->data['payment_option'][$tkey] = $order[$skey];
      }
      
      $order_items_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_ORDERS_ITEMS ."
        where order_id = '". (int)$order_id ."'
        order by id;"
      );
      while ($item = $GLOBALS['system']->database->fetch($order_items_query)) {
        $item['options'] = unserialize($item['options']);
        $this->data['items'][$item['id']] = $item;
      }
      
      $order_totals_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_ORDERS_TOTALS ."
        where order_id = '". (int)$order_id ."'
        order by priority;"
      );
      while ($row = $GLOBALS['system']->database->fetch($order_totals_query)) {
        $this->data['order_total'][$row['id']] = $row;
      }
      
      $order_comments_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_ORDERS_COMMENTS ."
        where order_id = '". (int)$order_id ."'
        order by id;"
      );
      while ($row = $GLOBALS['system']->database->fetch($order_comments_query)) {
        $this->data['comments'][$row['id']] = $row;
      }
    }
    
    public function save() {
      
    // Re-calculate total if there are changes
      $this->calculate_total();
      
    // Update purchase count
      if (empty($this->data['id'])) {
        if (!empty($this->data['items'])) {
          foreach (array_keys($this->data['items']) as $key) {
            if (!empty($this->data['items'][$key]['product_id'])) {
              $GLOBALS['system']->database->query(
                "update ". DB_TABLE_PRODUCTS ."
                set purchases = purchases + ". (int)$this->data['items'][$key]['quantity'] ."
                where id = ". (int)$this->data['items'][$key]['product_id'] ."
                limit 1;"
              );
            }
          }
        }
      }
      
      if (empty($this->data['uid'])) $this->data['uid'] = uniqid();
      
    // If changed order status 
      if (!empty($this->data['id'])) {
        $order_query = $GLOBALS['system']->database->query(
          "select order_status_id from ". DB_TABLE_ORDERS ."
          where id = ". (int)$this->data['id'] ."
          limit 1;"
        );
        $order = $GLOBALS['system']->database->fetch($order_query);
        
        if ((int)$order['order_status_id'] != (int)$this->data['order_status_id']) {
          $order_status_query = $GLOBALS['system']->database->query(
            "select os.*, osi.name from ". DB_TABLE_ORDER_STATUSES ." os
            left join ". DB_TABLE_ORDER_STATUSES_INFO ." osi on (os.id = osi.order_status_id and osi.language_code = '". $GLOBALS['system']->database->input($this->data['language_code']) ."')
            where os.id = ". (int)$this->data['order_status_id'] ."
            limit 1;"
          );
          $order_status = $GLOBALS['system']->database->fetch($order_status_query);
          
          if (!empty($order_status)) {
            $this->data['comments'][] = array(
              'text' => sprintf($GLOBALS['system']->language->translate('text_order_status_changed_to_s', 'Order status changed to %s'), $order_status['name']),
              'hidden' => 1,
            );
            
          // Send update notice e-mail
            if (!empty($order_status['notify'])) {
              $GLOBALS['system']->functions->email_send(
                '"'. $GLOBALS['system']->settings->get('store_name') .'" <'. $GLOBALS['system']->settings->get('store_email') .'>',
                $this->data['customer']['email'],
                sprintf($GLOBALS['system']->language->translate('title_order_d_updated', 'Order #%d Updated: %s', $this->data['language_code']), $this->data['id'], $order_status['name']),
                $this->draw_printable_copy(),
                true
              );
            }
          }
        }
      }
      
    // Link guests to customer profile
      if (empty($this->data['customer']['id'])) {
        $customers_query = $GLOBALS['system']->database->query(
          "select id from ". DB_TABLE_CUSTOMERS ."
          where email = '". $GLOBALS['system']->database->input($this->data['customer']['email']) ."'
          limit 1;"
        );
        $customer = $GLOBALS['system']->database->fetch($customers_query);
        if (!empty($customer['id'])) {
          $this->data['customer']['id'] = $customer['id'];
        }
      }
      
    // Insert/update order
      if (empty($this->data['id'])) {
        $GLOBALS['system']->database->query(
          "insert into ". DB_TABLE_ORDERS ."
          (uid, date_created)
          values ('". $GLOBALS['system']->database->input($this->data['uid']) ."', '". $GLOBALS['system']->database->input(date('Y-m-d H:i:s')) ."');"
        );
        $this->data['id'] = $GLOBALS['system']->database->insert_id();
      }
      
      $GLOBALS['system']->database->query(
        "update ". DB_TABLE_ORDERS ." set
        order_status_id = '". (int)$this->data['order_status_id'] ."',
        customer_id = '". (int)$this->data['customer']['id'] ."',
        customer_email = '". $GLOBALS['system']->database->input($this->data['customer']['email']) ."',
        customer_phone = '". $GLOBALS['system']->database->input($this->data['customer']['phone']) ."',
        customer_tax_id = '". $GLOBALS['system']->database->input($this->data['customer']['tax_id']) ."',
        customer_company = '". $GLOBALS['system']->database->input($this->data['customer']['company']) ."',
        customer_firstname = '". $GLOBALS['system']->database->input($this->data['customer']['firstname']) ."',
        customer_lastname = '". $GLOBALS['system']->database->input($this->data['customer']['lastname']) ."',
        customer_address1 = '". $GLOBALS['system']->database->input($this->data['customer']['address1']) ."',
        customer_address2 = '". $GLOBALS['system']->database->input($this->data['customer']['address2']) ."',
        customer_city = '". $GLOBALS['system']->database->input($this->data['customer']['city']) ."',
        customer_postcode = '". $GLOBALS['system']->database->input($this->data['customer']['postcode']) ."',
        customer_country_code = '". $GLOBALS['system']->database->input($this->data['customer']['country_code']) ."',
        customer_zone_code = '". $GLOBALS['system']->database->input($this->data['customer']['zone_code']) ."',
        shipping_company = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['company']) ."',
        shipping_firstname = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['firstname']) ."',
        shipping_lastname = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['lastname']) ."',
        shipping_address1 = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['address1']) ."',
        shipping_address2 = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['address2']) ."',
        shipping_city = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['city']) ."',
        shipping_postcode = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['postcode']) ."',
        shipping_country_code = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['country_code']) ."',
        shipping_zone_code = '". $GLOBALS['system']->database->input($this->data['customer']['shipping_address']['zone_code']) ."',
        shipping_option_id = '". ((!empty($this->data['shipping_option'])) ? $GLOBALS['system']->database->input($this->data['shipping_option']['id']) : false) ."',
        shipping_option_name = '". ((!empty($this->data['shipping_option'])) ? $GLOBALS['system']->database->input($this->data['shipping_option']['name']) : false) ."',
        shipping_tracking_id = '". ((!empty($this->data['shipping_tracking_id'])) ? $GLOBALS['system']->database->input($this->data['shipping_tracking_id']) : false) ."',
        payment_option_id = '". ((!empty($this->data['payment_option'])) ? $GLOBALS['system']->database->input($this->data['payment_option']['id']) : false) ."',
        payment_option_name = '". ((!empty($this->data['payment_option'])) ? $GLOBALS['system']->database->input($this->data['payment_option']['name']) : false) ."',
        payment_transaction_id = '". ((!empty($this->data['payment_transaction_id'])) ? $GLOBALS['system']->database->input($this->data['payment_transaction_id']) : false) ."',
        language_code = '". $GLOBALS['system']->database->input($this->data['language_code']) ."',
        currency_code = '". $GLOBALS['system']->database->input($this->data['currency_code']) ."',
        currency_value = '". (float)$this->data['currency_value'] ."',
        weight_total = '". (float)$this->data['weight_total'] ."',
        weight_class = '". $GLOBALS['system']->database->input($this->data['weight_class']) ."',
        payment_due = '". (float)$this->data['payment_due'] ."',
        tax_total = '". (float)$this->data['tax_total'] ."',
        client_ip = '". $_SERVER['REMOTE_ADDR'] ."',
        date_updated = '". date('Y-m-d H:i:s') ."'
        where id = '". (int)$this->data['id'] ."'
        limit 1;"
      );
      
    // Build array of item ids
      $item_ids = array();
      if (!empty($this->data['items'])) {
        foreach (array_keys($this->data['items']) as $key) {
          if (!empty($this->data['items'][$key]['id'])) $item_ids[] = $this->data['items'][$key]['id'];
        }
      }
      
    // Delete order items
      $order_items_query = $GLOBALS['system']->database->query(
        "select * from ". DB_TABLE_ORDERS_ITEMS ."
        where order_id = '". (int)$this->data['id'] ."'
        and id not in ('". @implode("', '", $item_ids) ."');"
      );
      while($order_item = $GLOBALS['system']->database->fetch($order_items_query)) {
        $GLOBALS['system']->database->query(
          "delete from ". DB_TABLE_ORDERS_ITEMS ."
          where order_id = '". (int)$this->data['id'] ."'
          and id = '". (int)$order_item['id'] ."'
          limit 1;"
        );
        
      // Restock
        $GLOBALS['system']->functions->catalog_stock_adjust($order_item['product_id'], $order_item['option_stock_combination'], $order_item['quantity']);
      }
      
    // Insert/update order items
      if (!empty($this->data['items'])) {
        foreach (array_keys($this->data['items']) as $key) {
          if (empty($this->data['items'][$key]['id'])) {
            $GLOBALS['system']->database->query(
              "insert into ". DB_TABLE_ORDERS_ITEMS ."
              (order_id)
              values ('". (int)$this->data['id'] ."');"
            );
            $this->data['items'][$key]['id'] = $GLOBALS['system']->database->insert_id();
            $GLOBALS['system']->functions->catalog_stock_adjust($this->data['items'][$key]['product_id'], $this->data['items'][$key]['option_stock_combination'], -$this->data['items'][$key]['quantity']);
          } else {
          // Update stock qty
            $orders_items_query = $GLOBALS['system']->database->query(
              "select quantity from ". DB_TABLE_ORDERS_ITEMS ."
              where id = '". (int)$this->data['items'][$key]['id'] ."'
              and order_id = '". (int)$this->data['id'] ."';"
            );
            $order_item = $GLOBALS['system']->database->fetch($orders_items_query);
            $GLOBALS['system']->functions->catalog_stock_adjust($this->data['items'][$key]['product_id'], $this->data['items'][$key]['option_stock_combination'], -($this->data['items'][$key]['quantity'] - $order_item['quantity']));
          }
          $GLOBALS['system']->database->query(
            "update ". DB_TABLE_ORDERS_ITEMS ." 
            set product_id = '". (int)$this->data['items'][$key]['product_id'] ."',
            option_stock_combination = '". $GLOBALS['system']->database->input($this->data['items'][$key]['option_stock_combination']) ."',
            options = '". $GLOBALS['system']->database->input(serialize($this->data['items'][$key]['options'])) ."',
            name = '". $GLOBALS['system']->database->input($this->data['items'][$key]['name']) ."',
            sku = '". $GLOBALS['system']->database->input($this->data['items'][$key]['sku']) ."',
            quantity = '". (float)$this->data['items'][$key]['quantity'] ."',
            price = '". (float)$this->data['items'][$key]['price'] ."',
            tax = '". (float)$this->data['items'][$key]['tax'] ."',
            weight = '". (float)$this->data['items'][$key]['weight'] ."',
            weight_class = '". $GLOBALS['system']->database->input($this->data['items'][$key]['weight_class']) ."'
            where order_id = '". (int)$this->data['id'] ."'
            and id = '". (int)$this->data['items'][$key]['id'] ."'
            limit 1;"
          );
        }
      }
      
    // Build array of order total ids
      $order_total_ids = array();
      if (!empty($this->data['order_total'])) {
        foreach (array_keys($this->data['order_total']) as $key) {
          if (!empty($this->data['order_total'][$key]['id'])) $order_total_ids[] = $this->data['order_total'][$key]['id'];
        }
      }
      
    // Delete order total items
      $GLOBALS['system']->database->query(
        "delete from ". DB_TABLE_ORDERS_TOTALS ."
        where order_id = '". (int)$this->data['id'] ."'
        and id not in ('". @implode("', '", $order_total_ids) ."');;"
      );
      
    // Insert/update order total
      if (!empty($this->data['order_total'])) {
        $i = 0;
        foreach (array_keys($this->data['order_total']) as $key) {
          if (empty($this->data['order_total'][$key]['id'])) {
            $GLOBALS['system']->database->query(
              "insert into ". DB_TABLE_ORDERS_TOTALS ."
              (order_id)
              values ('". (int)$this->data['id'] ."');"
            );
            $this->data['order_total'][$key]['id'] = $GLOBALS['system']->database->insert_id();
          }
          $GLOBALS['system']->database->query(
            "update ". DB_TABLE_ORDERS_TOTALS ." 
            set title = '". $GLOBALS['system']->database->input($this->data['order_total'][$key]['title']) ."',
            module_id = '". $GLOBALS['system']->database->input($this->data['order_total'][$key]['module_id']) ."',
            value = '". (float)$this->data['order_total'][$key]['value'] ."',
            tax = '". (float)$this->data['order_total'][$key]['tax'] ."',
            calculate = '". (empty($this->data['order_total'][$key]['calculate']) ? 0 : 1) ."',
            priority = '". $GLOBALS['system']->database->input(++$i) ."'
            where order_id = '". (int)$this->data['id'] ."'
            and id = '". (int)$this->data['order_total'][$key]['id'] ."'
            limit 1;"
          );
        }
      }
      
    // Build array of comments ids
      $comments_ids = array();
      if (!empty($this->data['comments'])) {
        foreach (array_keys($this->data['comments']) as $key) {
          if (!empty($this->data['comments'][$key]['id'])) $comments_ids[] = $this->data['comments'][$key]['id'];
        }
      }
      
    // Delete comments
      $GLOBALS['system']->database->query(
        "delete from ". DB_TABLE_ORDERS_COMMENTS ."
        where order_id = '". (int)$this->data['id'] ."'
        and id not in ('". @implode("', '", $comments_ids) ."');"
      );
      
    // Insert/update comments
      if (!empty($this->data['comments'])) {
        foreach (array_keys($this->data['comments']) as $key) {
          if (empty($this->data['comments'][$key]['id'])) {
            $GLOBALS['system']->database->query(
              "insert into ". DB_TABLE_ORDERS_COMMENTS ."
              (order_id, date_created)
              values ('". (int)$this->data['id'] ."', '". date('Y-m-d H:i:s') ."');"
            );
            $this->data['comments'][$key]['id'] = $GLOBALS['system']->database->insert_id();
            $this->data['comments'][$key]['date_created'] = date('Y-m-d H:i:s');
          }
          $GLOBALS['system']->database->query(
            "update ". DB_TABLE_ORDERS_COMMENTS ." 
            set text = '". $GLOBALS['system']->database->input($this->data['comments'][$key]['text']) ."',
            hidden = '". (empty($this->data['comments'][$key]['hidden']) ? 0 : 1) ."'
            where order_id = '". (int)$this->data['id'] ."'
            and id = '". (int)$this->data['comments'][$key]['id'] ."'
            limit 1;"
          );
        }
      }
      
      $GLOBALS['system']->cache->set_breakpoint();
    }
    
    public function delete() {
      if (empty($this->data['id'])) return;
    
    // Empty order first..
      $this->data['items'] = array();
      $this->data['order_total'] = array();
      $this->calculate_total();
      $this->save();
      
    // ..then delete
      $GLOBALS['system']->database->query(
        "delete from ". DB_TABLE_ORDERS ."
        where id = '". (int)$this->data['id'] ."'
        limit 1;"
      );
    }
    
    public function calculate_total() {
      $this->data['payment_due'] = 0;
      $this->data['tax_total'] = 0;
      $this->data['weight_total'] = 0;
      
      foreach ($this->data['items'] as $item) {
        $this->add_cost($item['price'], $item['tax'], $item['quantity']);
        $this->data['weight_total'] += $GLOBALS['system']->weight->convert($item['weight'], $item['weight_class'], $this->data['weight_class']) * $item['quantity'];
      }
      
      foreach ($this->data['order_total'] as $order_total) {
        if (!empty($order_total['calculate'])) {
          $this->add_cost($order_total['value'], $order_total['tax']);
        }
      }
    }
    
    public function add_item($item) {
      
      $key_i = 1;
      while (isset($this->data['items']['new'.$key_i])) $key_i++;
      
    // Round decimals
      $rounded_price = round($GLOBALS['system']->currency->calculate($item['price'], $this->data['currency_code']), $GLOBALS['system']->currency->currencies[$this->data['currency_code']]['decimals']);
      $item['price'] = $GLOBALS['system']->currency->convert($rounded_price, $this->data['currency_code'], $GLOBALS['system']->settings->get('store_currency_code'));
      
      $this->data['items']['new'.$key_i] = array(
        'id' => '',
        'product_id' => $item['product_id'],
        'options' => $item['options'],
        'option_stock_combination' => $item['option_stock_combination'],
        'name' => $item['name'][$GLOBALS['system']->language->selected['code']],
        'sku' => $item['sku'],
        'price' => $item['price'],
        'tax' => !empty($item['tax_class_id']) ? $GLOBALS['system']->tax->get_tax($item['price'], $item['tax_class_id'], $this->data['customer']['country_code'], $this->data['customer']['zone_code']) : $item['tax'],
        'quantity' => $item['quantity'],
      );
      
      $this->data['weight_total'] += $item['quantity'] * $GLOBALS['system']->weight->convert($item['weight'], $item['weight_class'], $GLOBALS['system']->settings->get('store_weight_class'));
      
      $this->add_cost($item['price'] * $item['quantity'], $this->data['items']['new'.$key_i]['tax']);
    }
    
    public function add_ot_row($row) {
      
      $key_i = 1;
      while (isset($this->data['order_total']['new'.$key_i])) $key_i++;
      
    // Round decimals
      $rounded_value = round($GLOBALS['system']->currency->calculate($row['value'], $this->data['currency_code']), $GLOBALS['system']->currency->currencies[$this->data['currency_code']]['decimals']);
      $row['value'] = $GLOBALS['system']->currency->convert($rounded_value, $this->data['currency_code'], $GLOBALS['system']->settings->get('store_currency_code'));
      
      $this->data['order_total']['new'.$key_i] = array(
        'id' => 0,
        'module_id' => $row['id'],
        'title' =>  $row['title'],
        'value' => $row['value'],
        'tax' => !empty($row['tax_class_id']) ? $GLOBALS['system']->tax->get_tax($row['value'], $row['tax_class_id'], $this->data['customer']['country_code'], $this->data['customer']['zone_code']) : $row['tax'],
        'calculate' => !empty($row['calculate']) ? 1 : 0,
      );
      
      if (!empty($row['calculate'])) $this->add_cost($row['value'], $row['tax']);
    }
    
    private function add_cost($gross, $tax, $quantity=1) {
      $this->data['payment_due'] += $gross * $quantity;
      $this->data['payment_due'] += $tax * $quantity;
      $this->data['tax_total'] += $tax * $quantity;
    }
    
    public function checkout_forbidden() {
      
      $required_fields = array(
        'email',
        'firstname',
        'lastname',
        'address1',
        'city',
        'country_code',
      );
      
      if ($GLOBALS['system']->functions->reference_get_postcode_required($this->data['customer']['country_code'])) $required_fields[] = 'postcode';
      if ($GLOBALS['system']->functions->reference_country_num_zones($this->data['customer']['country_code'])) $required_fields[] = 'zone_code';
      
      foreach ($required_fields as $field) {
        if (empty($this->data['customer'][$field])) return $GLOBALS['system']->language->translate('error_insufficient_customer_information', 'Insufficient customer information, please fill out all necessary fields.') /*. ' ('.$field.')'*/;
      }
      
      if ($this->data['customer']['different_shipping_address']) {
        $required_fields = array(
          'firstname',
          'lastname',
          'address1',
          'city',
          'country_code',
        );
      
        if ($GLOBALS['system']->functions->reference_get_postcode_required($this->data['customer']['shipping_address']['country_code'])) $required_fields[] = 'shipping_address[postcode]';
        if ($GLOBALS['system']->functions->reference_country_num_zones($this->data['customer']['shipping_address']['country_code'])) $required_fields[] = 'shipping_address[zone_code]';
        
        foreach ($required_fields as $field) {
          if (empty($this->data['customer']['shipping_address'][$field])) return $GLOBALS['system']->language->translate('error_insufficient_customer_information', 'Insufficient customer information, please fill out all necessary fields.') /*. ' (shipping_address['.$field.'])'*/;
        }
      }
      
      if ($this->data['payment_due'] > 0 && empty($order->data['payment_option_id'])) $errors[] = $GLOBALS['system']->language->translate('text_please_select_a_payment_option', 'Please select a payment option.');
      
      return false;
    }
    
    public function email_order_copy($email) {
    
      if (empty($email)) return;
    
      $GLOBALS['system']->functions->email_send(
        '"'. $GLOBALS['system']->settings->get('store_name') .'" <'. $GLOBALS['system']->settings->get('store_email') .'>',
        $email,
        $GLOBALS['system']->language->translate('title_order_copy', 'Order Copy') .' #'. $this->data['id'],
        $this->draw_printable_copy(),
        true
      );
    }
    
    public function draw_printable_copy() {
    
      $order = $this->data;
      
      ob_start();
      include(FS_DIR_HTTP_ROOT . WS_DIR_INCLUDES . 'printable_order_copy.inc.php');
      $output = ob_get_clean();
      
      return $output;
    }
    
    public function draw_printable_packing_slip() {
    
      $order = $this->data;
      
      ob_start();
      include(FS_DIR_HTTP_ROOT . WS_DIR_INCLUDES . 'printable_packing_slip.inc.php');
      $output = ob_get_clean();
      
      return $output;
    }
  }

?>