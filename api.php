<?php
header('Content-Type: application/json');

// Tentukan path secara terpisah agar lebih rapi
 $messages_dir = __DIR__ . '/messages/';
 $likes_file = __DIR__ . '/likes.txt'; // Kita taruh likes.txt di luar agar mudah diakses

// Buat folder messages jika belum ada (dilakukan di awal, bukan di dalam aksi saja)
if (!is_dir($messages_dir)) {
    mkdir($messages_dir, 0755, true);
}

 $action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. SIMPAN PESAN KONTAK
if ($action === 'save_message') {
    $name = strip_tags($_POST['name'] ?? 'Anonim');
    $email = strip_tags($_POST['email'] ?? '-');
    $msg = strip_tags($_POST['message'] ?? '');
    
    if (empty($msg)) {
        echo json_encode(['success' => false, 'message' => 'Pesan kosong']);
        exit;
    }

    $filename = date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '.txt';
    $content = "Dari: $name\nEmail: $email\nTanggal: " . date('d F Y H:i') . "\n\n$msg";
    
    if (file_put_contents($messages_dir . $filename, $content)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
    }
    exit;
}

// 2. UPDATE LIKE
if ($action === 'update_like') {
    $liked = filter_var($_POST['liked'], FILTER_VALIDATE_BOOLEAN);
    
    $current = file_exists($likes_file) ? (int)file_get_contents($likes_file) : 0;
    $newCount = $liked ? $current + 1 : max(0, $current - 1);
    
    if (file_put_contents($likes_file, $newCount) !== false) {
        echo json_encode(['success' => true, 'count' => $newCount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update likes. Cek permission folder.']);
    }
    exit;
}

// 3. GET LIKE COUNT (Saat awal load)
if ($action === 'get_likes') {
    $count = file_exists($likes_file) ? (int)file_get_contents($likes_file) : 0;
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
?>
