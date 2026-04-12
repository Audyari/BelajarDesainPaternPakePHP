<?php
class User
{
    private $profile;  // Awalnya null (belum di-load)

    public function getProfile()
    {
        // Cek: Apakah profile sudah di-load?
        if ($this->profile === null) {
            // Belum! Baru di-load sekarang (LAZY LOADING)
            echo "🔄 Loading profile dari database...\n";
            $this->profile = [
                'name' => 'Budi',
                'email' => 'budi@test.com',
                'age' => 25
            ];
        }
        return $this->profile;
    }
}

// PAKAI
$user = new User();

echo "User object sudah dibuat\n";
echo "Profile BELUM di-load\n\n";

// Pertama kali akses profile
$profile = $user->getProfile();  // ← Baru di-load di sini!
echo $profile['name'] . "\n\n";

// Kedua kali akses profile (langsung, gak reload)
$profile2 = $user->getProfile();
echo $profile2['email'];
