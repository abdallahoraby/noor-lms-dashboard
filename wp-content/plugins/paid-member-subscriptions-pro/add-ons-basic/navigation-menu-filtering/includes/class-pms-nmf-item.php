<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) )
    exit;

class PMS_IN_NMF_Item {
    public $ID;
    public $object_id;
    public $title;
    public $url;
    public $db_id            = 0;
    public $object           = 'custom';
    public $menu_item_parent = 0;
    public $type             = 'custom';
    public $target           = '';
    public $attr_title       = '';
    public $classes          = array();
    public $xfn              = '';
}
