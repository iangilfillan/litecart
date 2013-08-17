<?php

$app_config = array(
  'name' => $GLOBALS['system']->language->translate('title_tax', 'Tax'),
  'default' => 'tax_classes',
  'icon' => 'icon.png',
  'menu' => array(
    array(
      'title' => $GLOBALS['system']->language->translate('title_tax_classes', 'Tax Classes'),
      'doc' => 'tax_classes',
      'params' => array(),
    ),
    array(
      'title' => $GLOBALS['system']->language->translate('title_tax_rates', 'Tax Rates'),
      'doc' => 'tax_rates',
      'params' => array(),
    ),
  ),
  'docs' => array(
    'tax_classes' => 'tax_classes.inc.php',
    'edit_tax_class' => 'edit_tax_class.inc.php',
    'tax_rates' => 'tax_rates.inc.php',
    'edit_tax_rate' => 'edit_tax_rate.inc.php',
  ),
);

?>