<?php
  if (empty(session::$data['recently_viewed_products'])) return;

  if (settings::get('box_recently_viewed_products_num_items') == 0) return;
  
  $box_recently_viewed_products = new view();
  
  $box_recently_viewed_products->snippets['products'] = array();
  
  $count = 0;
  foreach(array_reverse(session::$data['recently_viewed_products'], true) as $key => $array) {
    if (++$count <= settings::get('box_recently_viewed_products_num_items')) {
      $box_recently_viewed_products->snippets['products'][$key] = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'thumbnail' => functions::image_thumbnail(FS_DIR_HTTP_ROOT . WS_DIR_IMAGES . $array['image'], 150, 150, 'FIT_USE_WHITESPACING'),
        'link' => document::ilink('product', array('product_id' => $array['id'])),
      );
    } else {
      unset(session::$data['recently_viewed_products'][$key]);
    }
  }
  
  echo $box_recently_viewed_products->stitch('views/box_recently_viewed_products');
?>