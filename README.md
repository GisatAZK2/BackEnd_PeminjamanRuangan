# Panduan Penggunaan Aplikasi **Peminjaman Ruang Rapat**

Dokumen ini berisi tata cara menjalankan aplikasi serta informasi mengenai tech stack yang digunakan.
Dokumen System API Ada Di File : **Peminjaman_Ruang_rapat.md**


---

## 🧰 **Tech Stack**

* **PHP**: versi **8+**
* **Composer**: dependency manager
* **MySQL**: database utama
* **Tailwind**: Styling Ui Web
* **Sweetalert**: Notification Ui Web

* **Docker**: untuk deployment (opsional)


---

## 🚀 **Cara Menjalankan Aplikasi**

### 1. **Deployment via VPS (Opsional)**

Jika ingin menjalankan aplikasi menggunakan Docker, cukup build dan jalankan menggunakan **Dockerfile** yang sudah disediakan.

```
docker build -t ruangrapat .
docker run -p 8000:8000 ruangrapat
```

---

### 2. **Install Dependencies dengan Composer**

Setelah project di-clone, jalankan:

```
composer install
```

---

### 3. **Buat File `.env`**

Buat file `.env` secara manual, baik di Windows maupun Linux.

Contoh minimal:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=ruang_rapat
```

---

### 4. **Melihat List Perintah Console**

Gunakan command berikut untuk menampilkan daftar perintah yang tersedia:

```
php console
```

---

### 5. **Menjalankan Migrasi Database**

Untuk membuat seluruh tabel yang dibutuhkan aplikasi:

```
php console migrate
```

---

### 6. **Menjalankan Seeder User**

Untuk menambahkan data user awal:

```
php console sedd
```

---

---

### 7. **Menjalankan Website**

Untuk Menjalankan:

```
php -S localhost:8080
```

---

## 🎉 Selesai!

Aplikasi kini siap digunakan dan dijalankan sesuai kebutuhan Anda.
