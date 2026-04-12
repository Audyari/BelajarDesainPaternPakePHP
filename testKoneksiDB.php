<?php
class DatabaseManager
{
    private static $instance = null;
    private $db;
    private $id;

    private function __construct()
    {
        $this->id = rand(1000, 9999);
        echo "🔧 Object baru dibuat dengan ID: {$this->id}\n";
    }
    private function __clone() {}

    public function getId()
    {
        return $this->id;
    }

    public static function getInstance()
    {

        echo "📌 SEBELUM: self::\$instance = " . (self::$instance === null ? "NULL" : "Object ID " . self::$instance->getId()) . "\n";

        if (self::$instance === null) {
            self::$instance = new self();
        }

        echo "📌 SESUDAH: self::\$instance = Object ID " . self::$instance->getId() . "\n\n";

        return self::$instance;
    }

    public function connect()
    {
        try {
            $this->db = new PDO('sqlite:memory');
            return "Koneksi SQLite BERHASIL!";
        } catch (PDOException $e) {
            return "Koneksi SQLite GAGAL: " . $e->getMessage();
        }
    }
}

// Eksekusi
$manager = DatabaseManager::getInstance();


$manager2 = DatabaseManager::getInstance();
//echo $manager->connect();
