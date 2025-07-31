<?php
class Encryption {
    private $key;
    private $cipher = "AES-256-CBC";
    private $iv_length;

    public function __construct() {
        $this->key = 'your-secret-key-here'; // 32 characters for AES-256
        $this->iv_length = openssl_cipher_iv_length($this->cipher);
    }

    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes($this->iv_length);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, $this->cipher, $this->key, 0, $iv);
    }
}
?>