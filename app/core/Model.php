<?php

/**
 * Temel Model Sınıfı
 * Tüm veritabanı modelleri bu sınıftan türetilir.
 * CRUD işlemlerini (Create, Read, Update, Delete) kolaylaştırır.
 */
class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function getDb() {
        return $this->db;
    }

    /**
     * Tüm kayıtları getirir
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * ID'ye göre tek bir kayıt getirir
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE `{$this->primaryKey}` = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Belirli bir sütuna göre filtreleme yapar
     */
    public function where($column, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE `{$column}` = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * Yeni kayıt ekler
     * @param array $data ['column' => 'value']
     */
    public function create($data) {
        $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    /**
     * Mevcut kaydı günceller
     * @param int $id
     * @param array $data ['column' => 'value']
     */
    public function update($id, $data) {
        $set = "";
        foreach ($data as $key => $value) {
            $set .= "`{$key}` = ?, ";
        }
        $set = rtrim($set, ', ');
        
        $sql = "UPDATE {$this->table} SET {$set} WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db->prepare($sql);
        
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }

    /**
     * Kaydı siler
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$id]);
    }
}
