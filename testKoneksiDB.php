<?php
class DatabaseManager
{
    private static $instance = null;
    private $db;
    private $id;

    // ✅ DI: PDO dikasih dari luar lewat constructor
    private function __construct(PDO $pdo)  // Bukan array, tapi object PDO!
    {
        $this->id = rand(1000, 9999);
        $this->db = $pdo;  // ← PDO udah jadi, tinggal pake
        echo "🔧 Object baru dibuat dengan ID: {$this->id}\n";
    }

    private function __clone() {}

    public function getId()
    {
        return $this->id;
    }

    // ✅ getInstance terima PDO object, bukan array
    public static function getInstance(?PDO $pdo = null)
    {
        echo "📌 SEBELUM: " . (self::$instance === null ? "NULL" : "Object ID " . self::$instance->getId()) . "\n";

        if (self::$instance === null) {
            if ($pdo === null) {
                throw new Exception("Wajib kasih PDO saat pertama kali!");
            }
            self::$instance = new self($pdo);  // ← DI terjadi di sini
        }

        echo "📌 SESUDAH: Object ID " . self::$instance->getId() . "\n\n";
        return self::$instance;
    }

    public function connect()
    {
        try {
            // ✅ Gak perlu bikin PDO, udah ada dari constructor!
            $this->db->query("SELECT 1");  // Test koneksi
            return "Koneksi BERHASIL!";
        } catch (PDOException $e) {
            return "Koneksi GAGAL: " . $e->getMessage();
        }
    }

    public function getDb()
    {
        return $this->db;
    }
}

// EKSEKUSI DENGAN DI YANG BENAR
if (!defined('PHPUNIT_TEST')) {
    $pdo = new PDO('sqlite:memory');  // ← Bikin PDO di LUAR
    $manager = DatabaseManager::getInstance($pdo);  // ← DI: PDO dikasih dari luar

    $manager2 = DatabaseManager::getInstance();  // Instance sama

    echo $manager->connect();

    // Bisa juga ganti ke MySQL (cukup ganti PDO-nya)
    // $pdo2 = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    // $manager3 = DatabaseManager::getInstance($pdo2);  // Ini bakal error karena singleton
}