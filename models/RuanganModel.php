<?php
class RuanganModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===============================
    // RUANGAN
    // ===============================
    public function getAllRooms()
    {
        $stmt = $this->pdo->query("SELECT * FROM Ruangan ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRoom($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Ruangan (ruangan_name) VALUES (?)");
        return $stmt->execute([$name]);
    }

    // ===============================
    // PEMINJAMAN
    // ===============================
    public function isRoomAvailable($ruangan_id, $tanggal_mulai, $tanggal_selesai, $jam_mulai, $jam_selesai)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM Pinjam_Ruangan 
            WHERE ruangan_id = ? 
              AND status IN ('pending','disetujui')
              AND (
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) 
                OR (tanggal_mulai <= ? AND tanggal_selesai >= ?)
              )
              AND (
                (jam_mulai <= ? AND jam_selesai >= ?) 
                OR (jam_mulai <= ? AND jam_selesai >= ?)
              )
        ");
        $stmt->execute([
            $ruangan_id,
            $tanggal_selesai, $tanggal_mulai,
            $tanggal_selesai, $tanggal_mulai,
            $jam_selesai, $jam_mulai,
            $jam_selesai, $jam_mulai
        ]);
        return $stmt->fetchColumn() == 0;
    }

    public function createBooking($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO Pinjam_Ruangan 
                (user_id, ruangan_id, kegiatan, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['ruangan_id'],
            $data['kegiatan'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai']
        ]);
    }

    public function updateStatus($id, $status, $keterangan = null)
    {
        $stmt = $this->pdo->prepare("UPDATE Pinjam_Ruangan SET status = ?, keterangan = ? WHERE id = ?");
        return $stmt->execute([$status, $keterangan, $id]);
    }

    public function getBookingById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT pr.*, u.nama AS nama_user, r.ruangan_name 
            FROM Pinjam_Ruangan pr
            JOIN table_user u ON pr.user_id = u.id_user
            JOIN Ruangan r ON pr.ruangan_id = r.id
            WHERE pr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // NOTULEN MULTI-FILE (AUTO WEBP with safe fallbacks)
    // - stores files_json (array meta) and files_blob (array base64)
    // ===============================
    public function uploadNotulenMulti($pinjam_id, $files)
    {
        $fileMetas = [];
        $fileContents = [];

        // Normalize single-file to array form
        if (!is_array($files['name'])) {
            $names = [$files['name']];
            $tmp_names = [$files['tmp_name']];
            $types = [$files['type']];
            $sizes = [$files['size']];
            $errors = [$files['error']];
        } else {
            $names = $files['name'];
            $tmp_names = $files['tmp_name'];
            $types = $files['type'];
            $sizes = $files['size'];
            $errors = $files['error'];
        }

        // Detect whether we can use GD or Imagick
        $hasGD = extension_loaded('gd');
        $hasImagick = class_exists('Imagick');

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $tmp = $tmp_names[$i];
            $type = $types[$i] ?? mime_content_type($tmp);
            $size = $sizes[$i] ?? (@filesize($tmp) ?: 0);
            $error = $errors[$i] ?? UPLOAD_ERR_OK;

            if ($error !== UPLOAD_ERR_OK) continue;
            if (!is_uploaded_file($tmp) && !file_exists($tmp)) {
                // allow for test cases where tmp is a path (not uploaded file)
                // continue; // comment out if you want to allow non-uploaded paths
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $content = file_get_contents($tmp);

            $isImage = false;
            // Determine image better
            $imageInfo = @getimagesize($tmp);
            if ($imageInfo !== false) {
                $isImage = true;
            }

            // Try convert images to webp only when image AND a conversion method available
            if ($isImage && in_array($extension, ['jpg','jpeg','png','webp','gif','bmp','tiff'])) {
                $converted = false;

                // Prefer Imagick if available (more robust)
                if ($hasImagick) {
                    try {
                        $im = new Imagick($tmp);
                        // convert to webp
                        $im->setImageFormat('webp');
                        // optionally set quality
                        $im->setImageCompressionQuality(75);
                        $content = $im->getImagesBlob();
                        $type = 'image/webp';
                        $name = pathinfo($name, PATHINFO_FILENAME) . '.webp';
                        $size = strlen($content);
                        $im->clear();
                        $im->destroy();
                        $converted = true;
                    } catch (Exception $e) {
                        // fallback to GD or keep original
                        $converted = false;
                    }
                }

                // If not converted by Imagick, try GD (if supports needed codecs)
                if (!$converted && $hasGD) {
                    // map extensions to loader functions and check existence
                    $loader = null;
                    if (in_array($extension, ['jpg','jpeg']) && function_exists('imagecreatefromjpeg')) {
                        $loader = 'imagecreatefromjpeg';
                    } elseif ($extension === 'png' && function_exists('imagecreatefrompng')) {
                        $loader = 'imagecreatefrompng';
                    } elseif ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
                        $loader = 'imagecreatefromwebp';
                    } elseif ($extension === 'gif' && function_exists('imagecreatefromgif')) {
                        $loader = 'imagecreatefromgif';
                    } elseif ($extension === 'bmp' && function_exists('imagecreatefrombmp')) {
                        $loader = 'imagecreatefrombmp';
                    } elseif (in_array($extension, ['tif','tiff'])) {
                        // no guaranteed loader in GD; skip
                        $loader = null;
                    }

                    if ($loader) {
                        $img = @$loader($tmp);
                        if ($img !== false && $img !== null) {
                            // Ensure palette -> truecolor for png/gif
                            if (function_exists('imagepalettetotruecolor') && imageistruecolor($img) === false) {
                                @imagepalettetotruecolor($img);
                            }
                            // preserve alpha for png
                            if ($extension === 'png' && function_exists('imagesavealpha')) {
                                @imagealphablending($img, true);
                                @imagesavealpha($img, true);
                            }

                            // output to temporary file (safer than output buffering in some envs)
                            $tmp_out = tempnam(sys_get_temp_dir(), 'webp_');
                            if ($tmp_out !== false) {
                                // imagewebp may not exist if GD built without webp support
                                if (function_exists('imagewebp')) {
                                    // quality 75
                                    @imagewebp($img, $tmp_out, 75);
                                    $newContent = @file_get_contents($tmp_out);
                                    if ($newContent !== false && strlen($newContent) > 0) {
                                        $content = $newContent;
                                        $type = 'image/webp';
                                        $name = pathinfo($name, PATHINFO_FILENAME) . '.webp';
                                        $size = strlen($content);
                                        $converted = true;
                                    }
                                    @unlink($tmp_out);
                                }
                            }
                            @imagedestroy($img);
                        }
                    }
                }
                // If no conversion succeeded: leave original $content, $type, $name
            }

            // build meta and content arrays
            $meta = [
                'name' => $name,
                'type' => $type,
                'size' => $size
            ];
            $fileMetas[] = $meta;
            $fileContents[] = base64_encode($content);
        }

        if (empty($fileMetas)) return false;

        $json = json_encode($fileMetas);
        $blob = json_encode($fileContents);

        $stmt = $this->pdo->prepare("
            INSERT INTO Notulen_files (pinjam_id, files_json, files_blob)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE files_json = VALUES(files_json), files_blob = VALUES(files_blob)
        ");
        return $stmt->execute([$pinjam_id, $json, $blob]);
    }

    public function getNotulenFileByIndex($file_id, $index)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Notulen_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        $meta = json_decode($data['files_json'], true);
        $blobArray = json_decode($data['files_blob'], true);
        if (!isset($meta[$index]) || !isset($blobArray[$index])) return null;

        return [
            'pinjam_id' => $data['pinjam_id'],
            'meta' => $meta[$index],
            'data' => base64_decode($blobArray[$index])
        ];
    }

    public function markAsFinished($pinjam_id)
    {
        $stmt = $this->pdo->prepare("
            UPDATE Pinjam_Ruangan 
            SET status = 'selesai', keterangan = 'Rapat selesai dan notulen telah diunggah.'
            WHERE id = ?
        ");
        return $stmt->execute([$pinjam_id]);
    }

    // ===============================
    // HISTORI (multi-file)
    // ===============================
    public function getBookingHistory($user, $filter = 'semua')
    {
        $sql = "
            SELECT 
                pr.*, 
                u.nama AS nama_user, 
                r.ruangan_name,
                nf.id AS notulen_id,
                nf.files_json,
                nf.files_blob
            FROM Pinjam_Ruangan pr
            JOIN table_user u ON pr.user_id = u.id_user
            JOIN Ruangan r ON pr.ruangan_id = r.id
            LEFT JOIN Notulen_files nf ON nf.pinjam_id = pr.id
        ";

        $params = [];
        if ($user['role'] === 'peminjam') {
            $sql .= " WHERE pr.user_id = ?";
            $params[] = $user['id_user'];
        } else {
            $sql .= " WHERE 1=1";
        }

        if ($filter !== 'semua') {
            $sql .= " AND pr.status = ?";
            $params[] = $filter;
        }

        $sql .= " ORDER BY pr.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if (!empty($row['files_json'])) {
                $meta = json_decode($row['files_json'], true);
                $blobs = json_decode($row['files_blob'], true);
                $files = [];
                foreach ($meta as $i => $m) {
                    $files[] = [
                        'name' => $m['name'],
                        'type' => $m['type'],
                        'size' => $m['size'],
                        'preview_url' => 'data:' . $m['type'] . ';base64,' . ($blobs[$i] ?? ''),
                        'download_url' => "/api/downloadNotulen/{$row['notulen_id']}?index={$i}"
                    ];
                }
                $row['notulen'] = $files;
            } else {
                $row['notulen'] = null;
            }
            unset($row['files_json'], $row['files_blob']);
        }

        return $rows;
    }

    // ===============================
    // AUTO-SELESAI
    // ===============================
    public function getExpiredBookings()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM Pinjam_Ruangan 
            WHERE status = 'disetujui' 
              AND tanggal_selesai < (NOW() - INTERVAL 1 DAY)
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
