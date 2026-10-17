# Aplikasi To-Do List

Aplikasi web sederhana berbasis PHP untuk mencatat dan mengelola tugas harian.

## Fitur

- Menambahkan tugas.
- Mengedit judul tugas.
- Menandai tugas sebagai selesai atau belum selesai.
- Menandai semua tugas sebagai selesai.
- Menghapus satu tugas dengan konfirmasi.
- Menghapus semua tugas yang sudah selesai dengan konfirmasi.
- Mengembalikan daftar tugas ke data awal.

Daftar tugas disimpan menggunakan PHP session; aplikasi ini tidak memerlukan
database.

## Persyaratan

- PHP 7.4 atau lebih baru.
- Web server lokal, seperti Apache melalui XAMPP.
- Koneksi internet untuk memuat Bootstrap 5.3.3 dari CDN.

## Menjalankan Aplikasi

1. Salin atau clone repositori ini ke folder `htdocs` XAMPP.
2. Jalankan Apache dari XAMPP Control Panel.
3. Buka [http://localhost/todolist](http://localhost/todolist) di browser.

## Struktur Proyek

```text
todolist/
├── assets/
│   └── style.css   # Gaya aplikasi
├── index.php       # Aplikasi dan pemrosesan tugas
└── README.md
```

## Kontributor

- [Tri Wulandari](https://github.com/triwulandari-git)
