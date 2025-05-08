<?php
class storeHelper
{
    private $table_name = "";

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_store_list';
    }

    public function tableName()
    {
        return $this->table_name;
    }

    public function getStoreList($store_id = null)
    {
        global $wpdb;
        $query = "SELECT * FROM {$this->table_name}";
        if ($store_id) {
            $query .= " WHERE id = %d";
            return $wpdb->get_row($wpdb->prepare($query, $store_id));
        } else {
            return $wpdb->get_results($query);
        }
    }

    public function getStoreName($store_id = null)
    {
        global $wpdb;
        if ($store_id) {
            $query = "SELECT `name` FROM {$this->table_name} WHERE id = '$store_id' ";
            return $wpdb->get_var($wpdb->prepare($query, $store_id));
        }
        return null;
    }
}
