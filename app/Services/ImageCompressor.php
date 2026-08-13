<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Kompresi gambar bukti transfer sebelum disimpan di server:
 * resize ke maks 1600px + encode JPEG quality 80 (hasil jauh lebih kecil).
 */
class ImageCompressor
{
    public static function compressAndStore(
        UploadedFile $file,
        string $dir = 'uploads/proofs',
        int $maxDim = 1600,
        int $quality = 80,
    ): string {
        $src = $file->getRealPath();

        $size = @getimagesize($src);
        if (! $size) {
            throw new \InvalidArgumentException('Gambar tidak valid.');
        }

        [$w, $h, $type] = $size;

        $srcImg = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            IMAGETYPE_PNG => @imagecreatefrompng($src),
            IMAGETYPE_WEBP => @imagecreatefromwebp($src),
            default => throw new \InvalidArgumentException('Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.'),
        };

        if (! $srcImg) {
            throw new \InvalidArgumentException('Gagal membaca gambar.');
        }

        $scale = min(1, $maxDim / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);

        // Latar putih untuk PNG transparan agar hasil JPEG bersih
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);

        imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $name = uniqid('proof-', true).'.jpg';

        ob_start();
        imagejpeg($dst, null, $quality);
        $data = ob_get_clean();

        Storage::disk('public')->put($dir.'/'.$name, $data);

        imagedestroy($srcImg);
        imagedestroy($dst);

        return $dir.'/'.$name;
    }
}
