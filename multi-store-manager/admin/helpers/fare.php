<?php
class fareHelper
{
    private $table_name = "";

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_store_shipping_fare';
    }

    public function tableName()
    {
        return $this->table_name;
    }

    public function getFareList($store_id = null)
    {
        global $wpdb;
        $query = "SELECT * FROM {$this->table_name} WHERE `store_id`='$store_id' ";
        return $wpdb->get_results($wpdb->prepare($query, $store_id));
    }
}
