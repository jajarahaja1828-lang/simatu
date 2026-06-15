# 📦 SIMATU - Setup Guide

## Informasi Project
- **Nama:** SIMATU (Sistem Management Tata Usaha)
- **Versi:** 1.0
- **Framework:** PHP 7.4+ dengan MySQL
- **UI Framework:** Bootstrap Icons, Chart.js
- **Status:** Fully Functional with Premium UI/UX

---

## 🚀 Instalasi di Komputer Baru

### 1️⃣ Requirements
- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Edge, dll)

### 2️⃣ Setup Database

**Windows:**
```bash
cd C:\xampp\mysql\bin
mysql -u root -p"" simatu < C:\xampp\htdocs\simatu\init.sql
```

**Linux/Mac:**
```bash
/opt/lampp/bin/mysql -u root simatu < /opt/lampp/htdocs/simatu/init.sql
```

### 3️⃣ Konfigurasi File

Edit file: `includes/config.php`
```php
define('BASE_URL', 'http://localhost/simatu/public');
define('BASE_PATH', '/simatu/public');
```

Jika domain berbeda, sesuaikan URL-nya.

### 4️⃣ Setup Folder

**Windows:**
```
C:\xampp\htdocs\simatu\
```

**Linux/Mac:**
```
/opt/lampp/htdocs/simatu/
```

Berikan permission folder:
```bash
chmod -R 755 simatu/
chmod -R 777 simatu/public/uploads/
```

### 5️⃣ Jalankan Aplikasi

1. Buka XAMPP Control Panel
2. Start **Apache** dan **MySQL**
3. Buka browser: `http://localhost/simatu/public/`

---

## 👤 Default Credentials

Cek file `init.sql` untuk default users atau setup user baru via:
```php
// Login form: public/login.php
```

---

## 📂 Struktur Project

```
simatu/
├── public/              # Web Root
│   ├── index.php       # Homepage
│   ├── login.php       # Login
│   ├── dashboard.php   # Dashboard
│   ├── router.php      # Router
│   ├── api/            # API endpoints
│   ├── bmn/            # Modul BMN
│   ├── keuangan/       # Modul Keuangan
│   ├── kepegawaian/    # Modul Kepegawaian
│   ├── persediaan/     # Modul Persediaan
│   ├── profile/        # Profil User
│   ├── assets/         # CSS, JS, Images
│   └── uploads/        # User uploads
├── includes/           # Backend Logic
│   ├── config.php      # Configuration
│   ├── db.php          # Database
│   ├── layout.php      # Layout Template
│   └── auth.php        # Authentication
├── src/                # React Components (Optional)
├── init.sql            # Database Schema
├── package.json        # Dependencies
└── vite.config.ts      # Vite Config
```

---

## 🔧 Database Schema

Database secara otomatis dibuat dengan:
- `users` - User accounts
- `pegawai` - Employees
- `anggaran` - Budget
- `anggaran_history` - Budget history
- `bmn_kategori` - BMN Categories
- `bmn_aset` - BMN Assets
- `barang_persediaan` - Stock Items
- `transaksi_masuk` - Incoming Transactions
- `transaksi_keluar` - Outgoing Transactions
- `kenaikan_pangkat` - Promotion History

---

## 🎨 Fitur Utama

✅ **Premium UI/UX Design**
- Modern gradient backgrounds
- Smooth animations & transitions
- Responsive design (mobile-friendly)
- Custom CSS variables system

✅ **Modul Keuangan**
- Dashboard anggaran
- Tracking belanja pegawai & barang
- Historical reports
- Print laporan

✅ **Modul Kepegawaian**
- CRUD pegawai
- Golongan distribution
- Kenaikan pangkat tracking
- Live search filtering

✅ **Modul BMN**
- Aset tracking
- Kategori management
- Laporan terperinci
- Edit/Delete dengan modal

✅ **Modul Persediaan**
- Stock management
- In/Out transactions
- Stock reports

---

## 🛠️ Troubleshooting

### Error: "Base table not found"
```bash
# Re-import database
mysql -u root -p"" simatu < init.sql
```

### Error: "Access Denied"
- Check username/password di `includes/config.php`
- Verifikasi credentials di MySQL

### Error: "Redirect not working"
- Pastikan `BASE_PATH` di `config.php` sesuai dengan struktur folder
- Clear browser cache (Ctrl+Shift+Delete)

### UI Tidak Tampil
- Refresh halaman dengan Ctrl+Shift+R
- Check browser console (F12) untuk errors

---

## 📝 Catatan Penting

1. **Database Backup**
   ```bash
   mysqldump -u root simatu > backup.sql
   ```

2. **Upload Folder Permissions**
   - Folder `public/uploads/` harus writable
   - Untuk keamanan, batasi tipe file yang bisa diupload

3. **Session Settings**
   - Default session timeout: 30 menit
   - Edit di `includes/auth.php` jika diperlukan

4. **Security**
   - Ganti default users dengan password kuat
   - Update `includes/config.php` untuk production
   - Implement HTTPS untuk production

---

## 📞 Support & Contact

Untuk bantuan lebih lanjut:
- Cek file `includes/` untuk memahami struktur database
- Review `public/` untuk flow aplikasi
- Check `init.sql` untuk schema lengkap

---

**Last Updated:** 2026-06-15  
**Version:** 1.0  
**Status:** ✅ Ready for Production
