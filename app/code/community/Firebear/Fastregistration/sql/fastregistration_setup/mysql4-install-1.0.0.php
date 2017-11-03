<?php
/**
 * @category   Firebear
 * @package    Firebear_Fastregistration
 * @version    1.0.0
 * @copyright  Copyright (c) 2013 Magento <fbeardev@gmail.com>
 */

/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer = $this;

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');

// update customer system attributes data
$attributes = array(
    "firstname" => array(
        "is_required" => 0,
        "validate_rules" => array(
            'max_text_length'   => 255,
            'min_text_length'   => 0
        )
    ),
    "lastname"  => array(
        "is_required" => 0,
        "validate_rules" => array(
            'max_text_length'   => 255,
            'min_text_length'   => 0
        )
    )
);

foreach($attributes as $code => $data){
    $attribute = $eavConfig->getAttribute('customer', $code);
    $attribute->addData($data);
    $attribute->save();
}