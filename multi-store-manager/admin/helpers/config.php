<?php
class configHelper
{
    private $table_name = "";

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_store_configs';
    }

    public function tableName()
    {
        return $this->table_name;
    }

    public function getConfig($key)
    {
        global $wpdb;
        $config = $wpdb->get_row($wpdb->prepare("SELECT `config_value` FROM {$this->table_name} WHERE `config_key` = %s", $key), ARRAY_A);
        return $config["config_value"]??"-";
    }
}
