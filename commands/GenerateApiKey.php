<?php

namespace Commands;

class GenerateApiKey
{
    public static function run()
    {
        $key = bin2hex(random_bytes(16));
        $dir = __DIR__ . '/../keys';
        $file = $dir . '/api_keys.txt';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, $key . PHP_EOL, FILE_APPEND);

        echo "✅ API Key baru berhasil dibuat:\n";
        echo "👉 $key\n";
        echo "📁 Disimpan di: $file\n";
    }
}
