# Sistem Manajemen Peminjaman Ruang Rapat

Sistem backend berbasis PHP untuk mengelola peminjaman ruang rapat, user, divisi, dan notulen. Menggunakan **PDO**, **caching (PSR-6)**, **transaksi database**, **locking**, dan **middleware autentikasi berbasis cookie**.

> **Tanggal Pembuatan:** 10 November 2025  
> **Teknologi:** PHP 8+, MySQL, PSR-16 Cache, Cookie Auth

> **Base Url Api :** https://backendpeminjamanruangan-production-f204.up.railway.app

---

## Fitur Utama

| Fitur | Deskripsi |
|------|----------|
| **Manajemen User** | CRUD user, role-based access, edit request |
| **Manajemen Ruang** | Tambah, ubah, hapus, cek ketersediaan |
| **Peminjaman Ruang** | Booking dengan pengecekan konflik jadwal |
| **Persetujuan Booking** | Petugas menyetujui/menolak |
| **Upload Notulen** | Multi-file, base64 storage, max 16MB |
| **Histori & Download** | Lihat riwayat, unduh notulen |
| **Auto-Finish** | Booking otomatis selesai setelah 1 hari |
| **Caching** | PSR-6, invalidate otomatis |
| **Keamanan** | Middleware role, transaksi, row lock |

---

## Struktur Role

| Role | Hak Akses |
|------|----------|
| `administrator` | Semua fitur (CRUD semua) |
| `petugas` | Kelola peminjam, setujui booking |
| `peminjam` | Booking ruang, upload notulen |

---

## Autentikasi

- **Login:** `POST /api/login` → simpan `user_info` di cookie (JSON encoded)
- **Logout:** `POST /api/logout` → hapus cookie
- **Middleware:** `AuthMiddleware::requireRole([...])`
- **Cookie:** `user_info` (HTTP-only: false, SameSite: None, Secure: HTTPS)

---

## API Endpoints

### 1. Autentikasi

#### `POST /api/login`
**Login user**

**Body (form-data):**
```text
username: string
password: string
```

**Response:**
```json
{
  "status": "success",
  "message": "Login berhasil!",
  "user_info": {
    "id_user": 1,
    "username": "admin",
    "role": "administrator",
    "nama": "Admin Utama"
  }
}
```

> Cookie `user_info` diset selama 1 jam

---

#### `POST /api/logout`
**Logout & hapus sesi**

**Response:**
```json
{ "status": "success", "message": "Logout berhasil!" }
```

---

### 2. Manajemen User (`/api/users/*`)

> **Middleware:** `AuthMiddleware::requireRole()` diterapkan

#### `GET /api/users`
**Ambil semua user (admin/petugas)**

| Role | Filter |
|------|-------|
| `administrator` | Semua user (kecuali diri sendiri) |
| `petugas` | Hanya `peminjam` |

**Response:**
```json
{
  "status": "success",
  "message": "Daftar user berhasil diambil.",
  "data": [
    {
      "id_user": 2,
      "username": "budi",
      "nama": "Budi Santoso",
      "role": "peminjam",
      "email": "budi@company.com",
      "id_divisi": 1,
      "nama_divisi": "IT"
    }
  ]
}
```

---

#### `GET /api/users/detail`
**Ambil detail user (bisa diri sendiri atau orang lain)**

**Query:** `?id_user=2`

| Role | Akses |
|------|-------|
| `peminjam` | Hanya diri sendiri |
| `petugas` | Diri sendiri + peminjam |
| `administrator` | Semua |

**Response:** Data user lengkap
```json
{
    "status": "success",
    "message": "Data user ditemukan.",
    "data": {
        "id_user": 8,
        "username": "admin01",
        "password_hash": "{example_password}",
        "role": "administrator",
        "nama": "SukiLine",
        "email": "admin@gmail.com",
        "nomor_telepon": "0867558999",
        "id_divisi": 1,
        "nama_divisi": "IT Support",
        "is_logged_in": 1
    }
}

```

---

#### `POST /api/users/add`
**Tambah user baru**

**Role:** `administrator`, `petugas`  
**Petugas hanya bisa tambah `peminjam`**

**Jika Administrator**
**Body:**
```json
{
  "username": "joko",
  "nama": "Joko Widodo",
  "email": "joko@company.com",
  "password": "rahasia123",
  "role": "peminjam",
  "nomor_telepon": "08123456789",
  "id_divisi": 2
}
```
**Jika Petugas (Default Role Selalu Peminjam)**
**Body:**
```json
{
  "username": "joko",
  "nama": "Joko Widodo",
  "email": "joko@company.com",
  "password": "rahasia123",
  "nomor_telepon": "08123456789",
  "id_divisi": 2
}
```


#### `GET /api/statistik`
**Get Statistik Data**

**Role:** `All Role`  

**Jika Administrator**
**Response:**
```json
{
  "status": "success",
  "role": "administrator",
  "data": {
    "total_user": 20,
    "total_divisi": 5,
    "total_ruangan": 8,
    "total_peminjaman": 50,
    "total_petugas": 3,
    "total_peminjam": 12,
    "peminjaman_per_hari": [
      {"tanggal": "2025-11-12", "total": 5},
      {"tanggal": "2025-11-11", "total": 3}
    ],
    "peminjaman_per_status": [
      {"status": "pending", "total": 2},
      {"status": "disetujui", "total": 5}
    ]
  }
}
```
**Jika Petugas**
**Response:**
```json
{
  "status": "success",
  "role": "petugas",
  "data": {
    "total_peminjaman": 50,
    "peminjaman_per_status": [
      {"status": "pending", "total": 2},
      {"status": "disetujui", "total": 5}
    ],
    "peminjaman_per_hari": [
      {"tanggal": "2025-11-12", "total": 5}
    ],
    "total_peminjam": 12,
    "total_ruangan": 8
  }
}
```
**Jika Peminjam**
**Response:**
```json
{
  "status": "success",
  "role": "peminjam",
  "data": {
    "total_pengajuan": 4,
    "total_disetujui": 2,
    "total_ditolak": 1
  }
}
```
---


---

#### `PUT /api/users/update`
**Update user**

**Only Administartor Can Edit Any Role ("Administrator", "Petugas", "Peminjam") And  Used Endpoint Change Role**

**Role:** Semua (terbatas)  
**Body:** Field yang ingin diubah + `id_user`
**Example (Not From Role Administrator) (form-data):**
```json
{
"id_user": 9,
"nama": "SkiSuki Updated",
"nomor_telepon": "087612433",
 "username" : "teriyaki", 
 "email": "gitasasd@gmail.com", 
 "password" : "Ambadaba1", 
 "id_divisi" : 2
 }
```


> **Catatan:**  
> - `peminjam` → hanya update diri sendiri  
> - `petugas` → hanya update `peminjam`  
> - `administrator` → semua kecuali diri sendiri  
> - `password` → di-hash otomatis  
> - `role` → **dilarang diubah di sini**

---

#### `DELETE /api/users/delete`
**Hapus user**

**Role:** `administrator` (tidak boleh hapus diri sendiri)

**Body:**
```json
{ "id_user": 5 }
```

---

#### `POST /api/users/request-edit`
**Petugas ajukan edit user peminjam ke admin**

**Role:** `petugas`  
**Body:**
```json
{ "target_user": 5 }
```

> Kirim email notifikasi ke admin

---

#### `POST /api/users/change-role`
**Ubah role user (admin only)**

**Role:** `administrator`  
**Body:**
```json
{
  "target_user": 3,
  "new_role": "petugas"
}
```

> Tidak boleh ubah Administrator diri sendiri

---

### 3. Manajemen Divisi (`/api/divisi/*`)

> **Only User Role `administrator`**

#### `GET /api/divisi`
**Ambil semua divisi (cache 1 jam)**

#### `GET /api/divisi/{id}`
**Ambil divisi berdasarkan ID (cache per ID)**

#### `POST /api/divisi`
**Tambah divisi**
```json
{ "nama_divisi": "Keuangan" }
```

#### `PUT /api/divisi/{id}`
**Update divisi**
**Example body Data**
```json
{
  "nama_divisi": "IT Support"
}
```

#### `DELETE /api/divisi/{id}`
**Hapus divisi**

> Cache otomatis di-invalidate

---

### 4. Manajemen Ruang Rapat Khusus Untuk 'Administrator' (`/api/ruangan/*`, `/api/AddRoom`, dll)

#### `GET /api/ruangan`
**Ambil semua ruangan (cache 5 menit)**

#### `GET /api/ruangan/{id}`
**Detail ruangan**

#### `POST /api/AddRoom`
**Tambah ruangan (admin)**
```json
{ "ruangan_name": "Ruang Rapat VIP" }
```

#### `PUT /api/ruangan/{id}`
**Update nama ruangan**

#### `DELETE /api/ruangan/{id}`
**Hapus ruangan**

---

### 5. Peminjaman Ruang

#### `POST /api/BookingRoom`
**Ajukan peminjaman (peminjam)**

**Body:**
```json
{
  "ruangan_id": 1,
  "kegiatan": "Rapat Bulanan IT",
  "tanggal_mulai": "2025-11-15",
  "tanggal_selesai": "2025-11-15",
  "jam_mulai": "09:00:00",
  "jam_selesai": "11:00:00"
}
```

> **Transaksi + Row Lock** → cegah double booking

---

#### `POST /api/UpdateStatusBooking/{id}`
**Setujui / Tolak (petugas)**

**Body:**
```json
{
  "status": "disetujui",
  "keterangan": "Ruang tersedia"
}
```

---

#### `POST /api/RoomFinished/{id}`
**Selesaikan rapat & upload notulen (peminjam)**

**Form-data:**
```text
files[]: (file1.pdf, file2.docx)
```

> Max 16MB per file, multi-upload, simpan base64

---

#### `GET /api/downloadNotulen/{id}`
**Download notulen (JSON base64)**

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "notulen.pdf",
    "type": "application/pdf",
    "size": 102400,
    "base64": "JVBERi0xLjQ..."
  }
}
```

> Akses: pemilik booking atau petugas/admin

---

#### `GET /api/GetHistory`
**Riwayat peminjaman**

**Query:** `?filter=semua|pending|disetujui|ditolak|selesai`

> **FIX N+1** → JOIN + `GROUP_CONCAT` notulen

**Example Response (Please FrontEnd Convert Base64 String To File )**
```json

{
    "status": "success",
    "message": "Histori peminjaman berhasil diambil.",
    "data": [
        {
            "id": 3,
            "user_id": 11,
            "ruangan_id": 3,
            "kegiatan": "Rapat Ambatublow",
            "tanggal_mulai": "2025-11-09",
            "tanggal_selesai": "2025-11-09",
            "jam_mulai": "12:00:00",
            "jam_selesai": "21:00:00",
            "status": "selesai",
            "tanggal_selesai_rapat": "2025-11-09 20:58:00",
            "keterangan": "Rapat selesai dan notulen telah diunggah.",
            "created_at": "2025-11-09 20:55:02",
            "nama_user": "New Peminjam",
            "role_user": "peminjam",
            "ruangan_name": "Ambatublow",
            "notulen": [
                {
                    "id": 7,
                    "name": "4815d6138173f461a7a60f654b1103f3 (1).jpg",
                    "type": "image/jpeg",
                    "size": 35708,
                    "preview_url": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQ......",
                    "download_url": "/api/downloadNotulen/7"
                }
            ]
        }
    ]
}

```

---

#### `GET /api/roomAvailability`
**Cek ketersediaan ruang**

**Query:** `?ruangan_id=1`

**Catatana : Hanya Ada Ketika Suatu Ruangan Telah Disteujui**

**Response:**

```json
{
    "status": "success",
    "message": "Availabilities fetched.",
    "data": [
        {
            "id": 4,
            "tanggal_mulai": "2025-11-20",
            "tanggal_selesai": "2025-11-24",
            "jam_mulai": "09:00:00",
            "jam_selesai": "11:00:00",
            "user_id": 11
        }
    ]
}

```

---

#### `POST /api/AutoFinishRoom`
**Otomatis selesaikan booking >1 hari (cron)**

> Update status → `selesai`

---

## Caching Strategy (PSR-6)

| Data | Key | TTL |
|------|-----|-----|
| User by username | `user_by_username_{md5}` | 5 menit |
| User by ID | `user_by_id_{id}` | 5 menit |
| Semua user | `all_users_all` / `all_users_{role}` | 2 menit |
| Semua ruangan | `ruangan_all` | 5 menit |
| Booking history | `booking_history_user_{id}_{filter}` | 2 menit |
| Notulen files | `booking_notulen_{pinjam_id}` | 10 menit |
| Approved bookings | `approved_bookings_room_{id}` | 5 menit |
| Divisi | `divisi.all`, `divisi.id.{id}` | 1 jam |

> **Invalidate otomatis** saat insert/update/delete

---

## Keamanan & Best Practices

| Fitur | Implementasi |
|------|--------------|
| **Password** | `password_hash(..., PASSWORD_DEFAULT)` |
| **SQL Injection** | Prepared statements |
| **Race Condition** | Transaksi + `FOR UPDATE` |
| **Double Booking** | Row lock + cek konflik |
| **File Upload** | Max 16MB, base64, validasi error |
| **Cookie** | Secure, SameSite=None, 1 jam |
| **Role Check** | Middleware terpusat |
| **Cache Invalidate** | Otomatis di setiap mutasi |

---

## Database Schema

```sql
Divisi → id_divisi, nama_divisi
User → id_user, username, password_hash, role, id_divisi, is_logged_in
Ruangan → id, ruangan_name
Pinjam_Ruangan → id, user_id, ruangan_id, status, tanggal, jam
Notulen_files → id, pinjam_id, file_name, data_base64
```

---

## Cara Menjalankan

1. Setup database dari migration
2. Konfigurasi `.env` (PDO, cache)
3. Jalankan cron: `POST /api/AutoFinishRoom` (setiap hari)
4. Akses via frontend atau Postman

---

## Kontributor

- Backend Developer: BackEnd Pemijaman Ruangan
- Sistem: PHP Native + PDO + PSR-6 Cache

---

> **Dibangun dengan performa, keamanan, dan skalabilitas.**
