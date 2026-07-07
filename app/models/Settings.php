<?php
namespace App\Models;

class Settings extends Model {
    /**
     * Get a setting by key. Returns default if key is not found.
     */
    public function get($key, $default = null) {
        $setting = $this->fetch("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1", ['key' => $key]);
        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Set/Save a setting value. Inserts if new, updates if existing.
     */
    public function set($key, $value) {
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value";
        return $this->query($sql, ['key' => $key, 'value' => $value]);
    }

    /**
     * Get all settings as a flat key => value array.
     */
    public function getAll() {
        $rows = $this->fetchAll("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
