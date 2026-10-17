<?php
/**
 * index.php - Aplikasi To-Do List
 * Fitur: tambah, edit, centang selesai, hapus (data array + session)
 */
session_start();

// ===== 1. DATA AWAL (struktur data array of objects sesuai soal) =====
// Session dipakai agar array tidak reset setiap halaman dimuat ulang
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [
        ["id" => 1, "title" => "Belajar PHP",       "status" => "belum"],
        ["id" => 2, "title" => "Kerjakan tugas UX", "status" => "selesai"],
    ];
}

$tasks = $_SESSION['tasks'];
// ===== 2. FUNGSI-FUNGSI (reusable) =====

// Membuat ID baru: ID terbesar + 1
function buatIdBaru(array $tasks): int {
    $ids = array_column($tasks, 'id');
    return $ids ? max($ids) + 1 : 1;
}

// Menambah tugas baru ke array
function tambahTugas(array $tasks, string $title): array {
    $title = trim($title);
    if ($title !== '') {
        $tasks[] = [
            "id"     => buatIdBaru($tasks),
            "title"  => $title,
            "status" => "belum"
        ];
    }
    return $tasks;
}

// Mengubah status tugas: belum <-> selesai (logika centang)
function ubahStatus(array $tasks, int $id): array {
    foreach ($tasks as $i => $task) {
        if ($task['id'] === $id) {
            $tasks[$i]['status'] = ($task['status'] === 'selesai') ? 'belum' : 'selesai';
        }
    }
    return $tasks;
}

// Menghapus satu tugas berdasarkan ID (logika penghapusan array)
function hapusTugas(array $tasks, int $id): array {
    $tasks = array_filter($tasks, fn($task) => $task['id'] !== $id);
    return array_values($tasks); // rapikan ulang index array
}

// Menghapus semua tugas yang sudah dicentang (selesai)
function hapusYangSelesai(array $tasks): array {
    $tasks = array_filter($tasks, fn($task) => $task['status'] !== 'selesai');
    return array_values($tasks);
}

// Mengubah judul tugas
function editTugas(array $tasks, int $id, string $title): array {
    $title = trim($title);
    if ($title === '') {
        return $tasks;
    }
    foreach ($tasks as $i => $task) {
        if ($task['id'] === $id) {
            $tasks[$i]['title'] = $title;
        }
    }
    return $tasks;
}

// Mencari satu tugas berdasarkan ID (dipakai untuk mode edit)
function cariTugas(array $tasks, int $id): ?array {
    foreach ($tasks as $task) {
        if ($task['id'] === $id) {
            return $task;
        }
    }
    return null;
}

// Menampilkan daftar tugas dengan foreach (sesuai soal: tampilkanDaftar())
function tampilkanDaftar(array $tasks): void {
    if (empty($tasks)) {
        echo '<li class="list-group-item text-muted text-center">Belum ada tugas.</li>';
        return;
    }

    foreach ($tasks as $task) {
        $selesai = ($task['status'] === 'selesai');
        $checked = $selesai ? 'checked' : '';
        $kelas   = $selesai ? 'task-selesai' : '';
        $badge   = $selesai
            ? '<span class="badge bg-success">selesai</span>'
            : '<span class="badge bg-secondary">belum</span>';
        $judul   = htmlspecialchars($task['title']); // cegah XSS
        $id      = $task['id'];

        echo '<li class="list-group-item d-flex align-items-center gap-2">';

        // CEKLIS: form kecil, otomatis submit saat dicentang
        echo '<form method="post" class="m-0">
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id" value="' . $id . '">
                <input class="form-check-input" type="checkbox" ' . $checked . '
                       onchange="this.form.submit()">
              </form>';

        echo '<span class="flex-grow-1 ' . $kelas . '">' . $judul . '</span>';
        echo $badge;

        // EDIT
        echo '<a href="index.php?edit=' . $id . '" class="btn btn-sm btn-outline-primary">Edit</a>';

        // HAPUS: berada di baris yang sama dengan ceklis
        echo '<form method="post" class="m-0" onsubmit="return confirm(\'Hapus tugas ini?\')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="' . $id . '">
                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
              </form>';

        echo '</li>';
    }
}

// ===== 3. PROSES AKSI (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = $_POST['aksi'] ?? '';
    $id    = (int)($_POST['id'] ?? 0);
    $title = $_POST['title'] ?? '';

    switch ($aksi) {
        case 'tambah':
            $tasks = tambahTugas($tasks, $title);
            break;
        case 'edit':
            $tasks = editTugas($tasks, $id, $title);
            break;
        case 'toggle':
            $tasks = ubahStatus($tasks, $id);
            break;
        case 'hapus':
            $tasks = hapusTugas($tasks, $id);
            break;
        case 'hapus_selesai':
            $tasks = hapusYangSelesai($tasks);
            break;
    }

    $_SESSION['tasks'] = $tasks;   // simpan perubahan
    header('Location: index.php'); // redirect agar tidak submit ulang saat refresh
    exit;
}

// ===== 4. MODE EDIT (jika ada ?edit=ID) =====
$tugasEdit = isset($_GET['edit']) ? cariTugas($tasks, (int)$_GET['edit']) : null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi To-Do List</title>

    <!-- Bootstrap 5 (library UI pre-existing) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS sederhana buatan sendiri -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="todo-container">

    <!-- HEADER -->
    <header class="text-center mb-4">
        <h1 class="h3">📝 Aplikasi To-Do List</h1>
        <p class="text-muted mb-0">Catat tugas harianmu</p>
    </header>

    <!-- FORM TAMBAH / EDIT TUGAS -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="post" class="d-flex gap-2">
                <?php if ($tugasEdit): ?>
                    <input type="hidden" name="aksi" value="edit">
                    <input type="hidden" name="id" value="<?= $tugasEdit['id'] ?>">
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($tugasEdit['title']) ?>">
                    <button type="submit" class="btn btn-warning">Simpan</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                <?php else: ?>
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="text" name="title" class="form-control"
                           placeholder="Tulis tugas baru..." required>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- DAFTAR TUGAS -->
    <ul class="list-group mb-3">
        <?php tampilkanDaftar($tasks); ?>
    </ul>

    <!-- HAPUS SEMUA YANG DICENTANG -->
    <form method="post" onsubmit="return confirm('Hapus semua tugas yang dicentang?')">
        <input type="hidden" name="aksi" value="hapus_selesai">
        <button type="submit" class="btn btn-outline-danger w-100">Hapus yang Dicentang</button>
    </form>

</div>

<!-- Untuk debugging: lihat isi array di Console browser (F12) -->
<script>
    console.log("Data tasks:", <?= json_encode($tasks) ?>);
</script>
</body>
</html>