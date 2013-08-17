<?php
  $system->document->layout = 'printable';

  $order = new ctrl_order($_GET['order_id']);
  
  if (!empty($_GET['product_id'])) $product = new ref_product($_GET['product_id'], $order->data['currency_code']);
?>
<script>
  $(document).ready(function() {
    parent.$('#fancybox-content').height($('body').height() + parseInt(parent.$('#fancybox-content').css('border-top-width')) + parseInt(parent.$('#fancybox-content').css('border-bottom-width')));
    parent.$.fancybox.center();
  });
</script>

<?php
  if (!empty($_POST['add'])) {
    
    $price = !empty($product->campaign['price']) ? $product->campaign['price'] : $product->price;
    $weight = $product->weight;
    $sku = $product->sku;
    
    if ($_POST['quantity'] <= 0) {
      if (!$silent) $GLOBALS['system']->notices->add('errors', 'Cannot add product to cart. Invalid product_id');
      return;
    }
    
    if (($product->quantity - $_POST['quantity']) < 0 && empty($product->sold_out_status['orderable'])) {
      if (!$silent) $GLOBALS['system']->notices->add('errors', $GLOBALS['system']->language->translate('text_not_enough_products_in_stock', 'There are not enough products in stock.'));
      return;
    }
    
    $_POST['options'] = array_filter($_POST['options']);
    $selected_options = array();
    
    if (count($product->options) > 0) {
      foreach (array_keys($product->options) as $key) {
        
        if ($product->options[$key]['required'] != 0) {
          if (empty($_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]])) {
            $system->notices->add('errors', $system->language->translate('error_set_product_options', 'Please set your product options') . ' ('. $product->options[$key]['name'][$system->language->selected['code']] .')');
            return;
          }
        }
        
        if (!empty($_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]])) {
          switch ($product->options[$key]['function']) {
            case 'checkbox':
            
              $valid_values = array();
              foreach ($product->options[$key]['values'] as $value) {
                $valid_values[] = $value['name'][$system->language->selected['code']];
                if (in_array($value['name'][$system->language->selected['code']], $_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]])) {
                  $selected_options[] = $product->options[$key]['id'].'-'.$value['id'];
                  $price += $value['price_adjust'];
                }
              }
              
              foreach ($_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]] as $current_value) {
                if (!in_array($current_value, $valid_values)) {
                  $system->notices->add('errors', $system->language->translate('error_product_options_contains_errors', 'The product options contains errors'));
                  return;
                }
              }
              break;
            
            case 'input':
            case 'textarea':
              $value = array_shift(array_values($product->options[$key]['values']));
              $selected_options[] = $product->options[$key]['id'].'-'.$value['id'];
              $price += $value['price_adjust'];
              break;
            
            case 'radio':
            case 'select':
            
              $valid_values = array();
              foreach ($product->options[$key]['values'] as $value) {
                $valid_values[] = $value['name'][$system->language->selected['code']];
                if ($value['name'][$system->language->selected['code']] == $_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]]) {
                  $selected_options[] = $product->options[$key]['id'].'-'.$value['id'];
                  $price += $value['price_adjust'];
                }
              }
              
              if (!in_array($_POST['options'][$product->options[$key]['name'][$system->language->selected['code']]], $valid_values)) {
                $system->notices->add('errors', $system->language->translate('error_product_options_contains_errors', 'The product options contains errors'));
                return;
              }
              
              break;
          }
        }
      }
    }
    
  // Match options with options stock
    if (count($product->options_stock) > 0) {
      foreach ($product->options_stock as $option_stock) {
      
        $option_match = true;
        foreach (explode(',', $option_stock['combination']) as $pair) {
          if (!in_array($pair, $selected_options)) {
            $option_match = false;
          }
        }
        
        if ($option_match) {
          if (($option_stock['quantity'] - $_POST['quantity']) < 0 && empty($product->sold_out_status['orderable'])) {
            $system->notices->add('errors', $system->language->translate('text_not_enough_products_in_stock_for_options', 'There are not enough products for the selected options.'));
            return;
          }
          
          $option_stock_combination = $option_stock['combination'];
          if (!empty($option_stock['weight'])) $weight = $system->weight->convert($option_stock['weight'], $option_stock['weight_class'], $product->weight_class);
          if (!empty($option_stock['sku'])) $sku = $option_stock['sku'];
          break;
        }
      }
    }
    
    if (!empty($system->notices->data['errors'])) {
      die(array_shift($system->notices->data['errors']));
    }
?>
<script>
  var new_row = '  <tr class="item">'
              + '    <td nowrap="nowrap" align="left">'
              + '      <?php echo $system->functions->form_draw_hidden_field('items[new_item_index][id]', ''); ?>'
              + '      <?php echo $system->functions->form_draw_hidden_field('items[new_item_index][product_id]', $product->id); ?>'
              + '      <?php echo $system->functions->form_draw_hidden_field('items[new_item_index][option_stock_combination]', !empty($option_stock_combination) ? $option_stock_combination : ''); ?>'
              + '      <?php echo $system->functions->form_draw_hidden_field('items[new_item_index][name]', $product->name[$system->language->selected['code']]); ?>'
              + '      <a href="<?php echo $system->document->href_link(WS_DIR_HTTP_HOME . 'product.php', array('product_id' => $product->id)); ?>" target="_blank"><?php echo $product->name[$system->language->selected['code']]; ?></a>'
<?php
    if (!empty($_POST['options'])) {
      echo '              + \'      <br />\'' . PHP_EOL
         . '              + \'      <table cellpadding="0">\'' . PHP_EOL;
      foreach (array_keys($_POST['options']) as $field) {
        echo '              + \'        <tr>\'' . PHP_EOL;
        if (is_array($_POST['options'][$field])) {
          echo '              + \'          <td style="padding-left: 10px;">'. $field .'</td>\'' . PHP_EOL
             . '              + \'          <td>\'';
          foreach (array_keys($_POST['options'][$field]) as $k) {
            echo $system->functions->form_draw_text_field('items[new_item_index][options]['.$field.']['. $_POST['options'][$field][$k] .']', true, !empty($option_stock_combination) ? 'readonly="readonly"' : '');
          }
          echo '              + \'          </td>\'' . PHP_EOL;
        } else {
          echo '              + \'          <td style="padding-left: 10px;">'. $field .'</td>\'' . PHP_EOL
             . '              + \'          <td> '. $system->functions->form_draw_text_field('items[new_item_index]['.$field.']', $_POST['options'][$field], !empty($option_stock_combination) ? 'readonly="readonly"' : '') .'</td>\'' . PHP_EOL;
        }
        echo '              + \'        </tr>\'' . PHP_EOL;
      }
      echo '              + \'    </table>\'' . PHP_EOL;
    }
?>
              + '    </td>'
              + '    <td nowrap="nowrap" align="center"><?php echo $system->functions->form_draw_hidden_field('items[new_item_index][sku]', $sku); ?><?php echo !empty($sku) ? $sku : $product->sku; ?></td>'
              + '    <td nowrap="nowrap" align="center"><?php echo $system->functions->form_draw_decimal_field('weight', $weight); ?> <?php echo str_replace(PHP_EOL, '', $system->functions->form_draw_weight_classes_list('weight_class', $product->weight_class)); ?></td>'
              + '    <td nowrap="nowrap" align="center"><?php echo $system->functions->form_draw_number_field('items[new_item_index][quantity]', $_POST['quantity']); ?></td>'
              + '    <td nowrap="nowrap" align="right"><?php echo $system->functions->form_draw_currency_field($order->data['currency_code'], 'items[new_item_index][price]', $price * $order->data['currency_value']); ?></td>'
              + '    <td nowrap="nowrap" align="right"><?php echo $system->functions->form_draw_currency_field($order->data['currency_code'], 'items[new_item_index][tax]', $system->tax->get_tax($price, $product->tax_class_id, $order->data['customer']['country_code'], $order->data['customer']['zone_code']) * $order->data['currency_value']); ?></td>'
              + '    <td nowrap="nowrap"><a class="remove_item" href="#"><img src="<?php echo WS_DIR_IMAGES; ?>icons/16x16/remove.png" width="16" height="16" title="<?php echo $system->language->translate('title_remove', 'Remove'); ?>" /></a></td>'
              + '  </tr>';
  
  var new_item_index = 0
  while ($("input[name='items["+new_item_index+"][id]']", window.parent.document).length) new_item_index++;
  new_row = new_row.replace(/new_item_index/g, "new_" + new_item_index);
  
  $("#order-items .footer", window.parent.document).before(new_row);
  parent.calculate_total();
  parent.$.fancybox.close();
</script>

<?php
  } else {
?>


<h1 style="margin-top: 0px;"><img src="<?php echo WS_DIR_ADMIN . $_GET['app'] .'.app/icon.png'; ?>" width="32" height="32" style="vertical-align: middle; margin-right: 10px;" /><?php echo $system->language->translate('title_add_product', 'Add Product'); ?></h1>

<?php echo $system->functions->form_draw_products_list('product_id', true, false, 'onchange="location=\''. str_replace('pid', '\'+ $(this).val() +\'', $system->document->link('', array('product_id' => 'pid'), true)) .'\'"'); ?>

<?php
  if (!empty($product)) {
    echo '<hr />' . PHP_EOL
       . $system->functions->form_draw_form_begin('form_add_product', 'post');
?>

  <?php if (!empty($product->options_stock)) {?>
  <div style="float: right; display: inline-block; border: 1px dashed #ccc; padding: 10px;">
    <h3 style="margin-top: 0px;"><?php echo $system->language->translate('title_options_stock', 'Options Stock'); ?></h3>
    <table>
      <?php foreach (array_keys($product->options_stock) as $key) { ?>
      <tr>
        <td><strong><?php echo $product->options_stock[$key]['name'][$system->language->selected['code']]; ?></strong></td>
        <td><?php echo $product->options_stock[$key]['quantity']; ?></td>
      </tr>
      <?php } ?>
    </table>
  </div>
  <?php } ?>
  
  <h2><?php echo $product->name[$system->language->selected['code']]; ?></h2>
  
  <table>
    <tr>
      <td><strong><?php echo $system->language->translate('title_price', 'Price'); ?></strong></td>
      <td><?php echo !empty($product->campaign['price']) ? '<s>'.$product->price.'</s> '. $system->currency->format($product->campaign['price'], true, false, $order->data['currency_code'], $order->data['currency_value']) : $system->currency->format($product->price, true, false, $order->data['currency_code'], $order->data['currency_value']); ?></td>
    </tr>
    <tr>
      <td><strong><?php echo $system->language->translate('title_tax', 'Tax'); ?></strong></td>
      <td><?php echo !empty($product->campaign['price']) ? '<s>'. $system->currency->format($system->tax->get_tax($product->price, $product->tax_class_id, $order->data['customer']['country_code'], $order->data['customer']['zone_code']), true, false, $order->data['currency_code'], $order->data['currency_value']) .'</s> ' . $system->currency->format($system->tax->get_tax($product->campaign['price'], $product->tax_class_id, $order->data['customer']['country_code'], $order->data['customer']['zone_code']), true, false, $order->data['currency_code'], $order->data['currency_value']) : $system->currency->format($system->tax->get_tax($product->price, $product->tax_class_id, $order->data['customer']['country_code'], $order->data['customer']['zone_code']), true, false, $order->data['currency_code'], $order->data['currency_value']); ?></td>
    </tr>
    <tr>
      <td><strong><?php echo $system->language->translate('title_stock_count', 'Stock Count'); ?></strong></td>
      <td><?php echo $product->quantity; ?></td>
    </tr>
    <tr>
      <td><strong><?php echo $system->language->translate('title_weight', 'Weight'); ?></strong></td>
      <td><?php echo $system->weight->format($product->weight, $product->weight_class); ?></td>
    </tr>
    <tr>
      <td><strong><?php echo $system->language->translate('title_quantity', 'Quantity'); ?></strong></td>
      <td><?php echo $system->functions->form_draw_number_field('quantity', !empty($_POST['quantity']) ? true : '1'); ?></td>
    </tr>
<?php
      if (count($product->options) > 0) {
        
        foreach ($product->options as $group) {
        
          echo '  <tr>' . PHP_EOL
             . '    <td><strong>'. $group['name'][$system->language->selected['code']] .'</strong>'. (empty($group['required']) == false ? ' <span class="required">*</span>' : '') .'<br />'
             . (!empty($group['description'][$system->language->selected['code']]) ? $group['description'][$system->language->selected['code']] . '</td>' . PHP_EOL : '')
             . '    <td>';
          
          switch ($group['function']) {
          
            case 'checkbox':
              $use_br = false;
              
              foreach (array_keys($group['values']) as $value_id) {
                if ($use_br) echo '<br />';
                
                $price_adjust_text = '';
                if ($group['values'][$value_id]['price_adjust']) {
                  $price_adjust_text = $system->currency->format($group['values'][$value_id]['price_adjust']);
                  if ($group['values'][$value_id]['price_adjust'] > 0) {
                    $price_adjust_text = ' +'.$price_adjust_text;
                  }
                }
                
                echo '<label>' . $system->functions->form_draw_checkbox('options['.$group['name'][$system->language->selected['code']].'][]', $group['values'][$value_id]['name'][$system->language->selected['code']], true, !empty($group['required']) ? 'required="required"' : '') .' '. $group['values'][$value_id]['name'][$system->language->selected['code']] . $price_adjust_text . '</label>' . PHP_EOL;
                $use_br = true;
              }
              break;
              
            case 'input':
            
              $value_id = array_shift(array_keys($group['values']));
            
              $price_adjust_text = '';
              if ($group['values'][$value_id]['price_adjust']) {
                $price_adjust_text = $system->currency->format($group['values'][$value_id]['price_adjust']);
                if ($group['values'][$value_id]['price_adjust'] > 0) {
                  $price_adjust_text = ' +'.$price_adjust_text;
                }
              }
              
              echo $system->functions->form_draw_text_field('options['.$group['name'][$system->language->selected['code']].']', true, !empty($group['required']) ? 'required="required"' : '') . $price_adjust_text . PHP_EOL;
              break;
              
            case 'radio':
            
              $use_br = false;
              foreach (array_keys($group['values']) as $value_id) {
                if ($use_br) echo '<br />';
                
                $price_adjust_text = '';
                if ($group['values'][$value_id]['price_adjust']) {
                  $price_adjust_text = $system->currency->format($group['values'][$value_id]['price_adjust']);
                  if ($group['values'][$value_id]['price_adjust'] > 0) {
                    $price_adjust_text = ' +'.$price_adjust_text;
                  }
                }
                
                echo '<label>' . $system->functions->form_draw_radio_button('options['.$group['name'][$system->language->selected['code']].']', $group['values'][$value_id]['name'][$system->language->selected['code']], true, !empty($group['required']) ? 'required="required"' : '') .' '. $group['values'][$value_id]['name'][$system->language->selected['code']] . $price_adjust_text . '</label>' . PHP_EOL;
                $use_br = true;
              }
              break;
              
            case 'select':
              
              $options = array(array('-- '. $system->language->translate('title_select', 'Select') .' --', ''));
              foreach (array_keys($group['values']) as $value_id) {
              
                $price_adjust_text = '';
                if ($group['values'][$value_id]['price_adjust']) {
                  $price_adjust_text = $system->currency->format($group['values'][$value_id]['price_adjust']);
                  if ($group['values'][$value_id]['price_adjust'] > 0) {
                    $price_adjust_text = ' +'.$price_adjust_text;
                  }
                }

                $options[] = array($group['values'][$value_id]['name'][$system->language->selected['code']] . $price_adjust_text, $group['values'][$value_id]['name'][$system->language->selected['code']]);
              }
              echo $system->functions->form_draw_select_field('options['.$group['name'][$system->language->selected['code']].']', $options, true, false, false, !empty($group['required']) ? 'required="required"' : '');
              break;
              
            case 'textarea':
              
              $value_id = array_shift(array_keys($group['values']));
              $price_adjust_text = '';
              if (!empty($group['values'][$value_id]['price_adjust'])) {
                $price_adjust_text = '';
                if ($group['values'][$value_id]['price_adjust'] > 0) {
                  $price_adjust_text = ' <br />+'. $system->currency->format($group['values'][$value_id]['price_adjust']);
                }
              }

              echo $system->functions->form_draw_textarea('options['.$group['name'][$system->language->selected['code']].']', true, !empty($group['required']) ? 'required="required"' : '') . $price_adjust_text. PHP_EOL;
              break;
          }
        }
        
        echo '    </td>' . PHP_EOL
           . '  </tr>' . PHP_EOL;
      }
?>
  </table>

  <p><?php echo $system->functions->form_draw_button('add', $system->language->translate('title_add', 'Add'), 'submit', '', 'add'); ?> <?php echo $system->functions->form_draw_button('cancel', $system->language->translate('title_cancel', 'Cancel'), 'button', 'onclick="parent.$.fancybox.close();"', 'cancel'); ?></p>

<?php
      echo $system->functions->form_draw_form_end();
    }
  }
?>