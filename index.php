<?php
/**
 * index.php - Daily Workspace
 * Fitur: tambah, edit (pop-up), centang selesai, hapus (pop-up konfirmasi)
 */
session_start();

// ===== 0. RESET DATA (untuk debugging: buka index.php?reset=1) =====
if (isset($_GET['reset'])) {
    unset($_SESSION['tasks']);
    header('Location: index.php');
    exit;
}

// ===== 1. DATA AWAL (array of objects) =====
if (!isset($_SESSION['tasks']) || !is_array($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [
        ["id" => 1, "title" => "Belajar PHP",       "status" => "belum"],
        ["id" => 2, "title" => "Kerjakan tugas UX", "status" => "selesai"],
    ];
}

// ===== 2. FUNGSI-FUNGSI (reusable) =====

// Merapikan data: ID dipaksa angka unik, status hanya "belum"/"selesai"
// (mencegah task lama/rusak yang tidak bisa diedit atau dihapus)
function normalisasi(array $tasks): array {
    $hasil = [];
    $dipakai = [];
    foreach ($tasks as $task) {
        $id = (int)($task['id'] ?? 0);
        if ($id <= 0 || in_array($id, $dipakai, true)) {
            $id = ($dipakai ? max($dipakai) : 0) + 1;
        }
        $dipakai[] = $id;
        $hasil[] = [
            "id"     => $id,
            "title"  => (string)($task['title'] ?? ''),
            "status" => (($task['status'] ?? '') === 'selesai') ? 'selesai' : 'belum',
        ];
    }
    return $hasil;
}

// Membuat ID baru: ID terbesar + 1
function buatIdBaru(array $tasks): int {
    $ids = array_column($tasks, 'id');
    return $ids ? max($ids) + 1 : 1;
}

// Menambah tugas baru
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

// Mengubah status: belum <-> selesai (logika centang)
function ubahStatus(array $tasks, int $id): array {
    foreach ($tasks as $i => $task) {
        if ((int)$task['id'] === $id) {
            $tasks[$i]['status'] = ($task['status'] === 'selesai') ? 'belum' : 'selesai';
        }
    }
    return $tasks;
}

// Menghapus satu tugas berdasarkan ID (logika penghapusan array)
function hapusTugas(array $tasks, int $id): array {
    $tasks = array_filter($tasks, fn($task) => (int)$task['id'] !== $id);
    return array_values($tasks);
}

// Menghapus semua tugas yang sudah dicentang
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
        if ((int)$task['id'] === $id) {
            $tasks[$i]['title'] = $title;
        }
    }
    return $tasks;
}

// Menampilkan daftar tugas dengan foreach
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
            ? '<span class="badge badge-selesai">selesai</span>'
            : '<span class="badge badge-belum">belum</span>';
        $judul   = htmlspecialchars($task['title'], ENT_QUOTES);
        $id      = (int)$task['id'];

        echo '<li class="list-group-item d-flex align-items-center gap-2">';

        // CEKLIS: otomatis submit saat dicentang
        echo '<form method="post" class="m-0">
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id" value="' . $id . '">
                <input class="form-check-input" type="checkbox" ' . $checked . '
                       onchange="this.form.submit()">
              </form>';

        echo '<span class="flex-grow-1 ' . $kelas . '">' . $judul . '</span>';
        echo $badge;

        // EDIT: membuka pop-up (modal)
        echo '<button type="button" class="btn btn-sm btn-gold-outline"
                data-bs-toggle="modal" data-bs-target="#modalEdit"
                data-id="' . $id . '" data-title="' . $judul . '">Edit</button>';

        // HAPUS: membuka pop-up konfirmasi (sebaris dengan ceklis)
        echo '<button type="button" class="btn btn-sm btn-soft-danger"
                data-bs-toggle="modal" data-bs-target="#modalHapus"
                data-id="' . $id . '" data-title="' . $judul . '">Hapus</button>';

        echo '</li>';
    }
}

// ===== 3. PROSES AKSI (POST) =====
$tasks = normalisasi($_SESSION['tasks']);

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
        case 'selesai_semua':
            foreach ($tasks as $i => $task) {
                $tasks[$i]['status'] = 'selesai';
            }
            break;
        case 'belum_semua':
            foreach ($tasks as $i => $task) {
                $tasks[$i]['status'] = 'belum';
            }
            break;
        case 'hapus':
            $tasks = hapusTugas($tasks, $id);
            break;
        case 'hapus_selesai':
            $tasks = hapusYangSelesai($tasks);
            break;
    }

    $_SESSION['tasks'] = $tasks;
    header('Location: index.php'); // cegah submit ulang saat refresh
    exit;
}

$_SESSION['tasks'] = $tasks; // simpan hasil normalisasi
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Workspace</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="todo-container">

    <!-- HEADER -->
    <header class="app-header">
        <h1 class="h3">Daily Workspace</h1>
        <p>Kelola tugas harian Anda.</p>
    </header>

    <!-- FORM TAMBAH TUGAS -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="post" class="d-flex gap-2">
                <input type="hidden" name="aksi" value="tambah">
                <input type="text" name="title" class="form-control"
                       placeholder="Tulis tugas baru..." required>
                <button type="submit" class="btn btn-navy">Tambah</button>
            </form>
        </div>
    </div>

    <!-- DAFTAR TUGAS -->
    <ul class="list-group mb-3 shadow-sm">
        <?php tampilkanDaftar($tasks); ?>
    </ul>

    <?php if (in_array('belum', array_column($tasks, 'status'), true)): ?>
        <form method="post" class="mb-2">
            <input type="hidden" name="aksi" value="selesai_semua">
            <button type="submit" class="btn btn-success w-100">
                Ceklis Semua Task
            </button>
        </form>
    <?php endif; ?>

    <?php if (in_array('selesai', array_column($tasks, 'status'), true)): ?>
        <form method="post" class="mb-2">
            <input type="hidden" name="aksi" value="belum_semua">
            <button type="submit" class="btn btn-outline-secondary w-100">
                Batalkan Ceklis Semua
            </button>
        </form>
    <?php endif; ?>

    <!-- HAPUS SEMUA YANG DICENTANG -->
    <button type="button" class="btn btn-soft-danger w-100"
            data-bs-toggle="modal" data-bs-target="#modalHapusSelesai">
        Hapus yang Dicentang
    </button>

    <div class="text-center mt-3">
        <a href="index.php?reset=1" class="reset-link"
           onclick="return confirm('Kembalikan data ke awal?')">Reset data</a>
    </div>
</div>

<!-- ===== POP-UP EDIT ===== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="edit">
        <input type="hidden" name="id" id="editId">
        <label for="editTitle" class="form-label">Judul tugas</label>
        <input type="text" name="title" id="editTitle" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-navy">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== POP-UP KONFIRMASI HAPUS ===== -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" id="hapusId">
        <p class="mb-0">Yakin ingin menghapus tugas <strong id="hapusJudul"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tidak</button>
        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== POP-UP HAPUS YANG DICENTANG ===== -->
<div class="modal fade" id="modalHapusSelesai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hapus Tugas Selesai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="hapus_selesai">
        <p class="mb-0">Semua tugas yang sudah dicentang akan dihapus. Lanjutkan?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Ya, Hapus Semua</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS (untuk pop-up) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Isi data tugas ke dalam pop-up saat dibuka
    document.getElementById('modalEdit').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editTitle').value = btn.dataset.title;
    });
    document.getElementById('modalEdit').addEventListener('shown.bs.modal', function () {
        document.getElementById('editTitle').focus();
    });
    document.getElementById('modalHapus').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('hapusId').value = btn.dataset.id;
        document.getElementById('hapusJudul').textContent = '"' + btn.dataset.title + '"';
    });

    // Untuk debugging: lihat array di Console (F12)
    console.log("Data tasks:", <?= json_encode($tasks) ?>);
</script>
</body>
</html>