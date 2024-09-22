<?php

namespace App\Http\Controllers;

class DecryptionController extends Controller
{
    // Método para receber e descriptografar os dados
    public static function decrypt($data)
    {
        // Receber os dados criptografados e o IV
        $encryptedData = base64_decode($data['data']);
        $iv = base64_decode($data['iv']);

        // Chave secreta usada para descriptografar (mesma do cliente)
        $encryptionKey = 'Joao-Pessoa-Paraiba';

        // Descriptografar os dados
        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $encryptionKey, 0, $iv);

        // Decodificar os dados de JSON para array
        $originalData = json_decode($decryptedData, false);

        // Agora você pode trabalhar com os dados
        return $originalData;
    }
}
