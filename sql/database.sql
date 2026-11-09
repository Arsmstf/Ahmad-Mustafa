CREATE DATABASE IF NOT EXISTS peminjaman_kantor CHARACTER SET utf8mb4;

USE peminjaman_kantor;

CREATE TABLE users (
	id_user INT AUTO_INCREMENT PRIMARY KEY,
	nama VARCHAR(100) NOT NULL,
	email VARCHAR(100) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan',
	status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kategori (
	id_kategori INT AUTO_INCREMENT PRIMARY KEY,
	nama_kategori VARCHAR(100) NOT NULL,
	deskripsi TEXT
);

CREATE TABLE barang (
	id_barang INT AUTO_INCREMENT PRIMARY KEY,
	id_kategori INT NOT NULL,
	kode_barang VARCHAR(50) NOT NULL UNIQUE,
	nama_barang VARCHAR(100) NOT NULL,
	jumlah_total INT NOT NULL,
	jumlah_tersedia INT NOT NULL,
	kondisi VARCHAR(50) DEFAULT 'Baik',
	lokasi VARCHAR(100),
	FOREIGN KEY(id_kategori) REFERENCES kategori(id_kategori)
);

CREATE TABLE peminjaman (
	id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
	id_user INT NOT NULL,
	id_admin_proses INT NULL,
	tanggal_pengajuan DATETIME DEFAULT CURRENT_TIMESTAMP,
	tanggal_pinjam DATE NOT NULL,
	tanggal_rencana_kembali DATE NOT NULL,
	status ENUM('Menunggu','Disetujui','Ditolak','Dipinjam','Selesai') NOT NULL DEFAULT 'Menunggu',
	catatan TEXT,
	FOREIGN KEY(id_user) REFERENCES users(id_user),
	FOREIGN KEY(id_admin_proses) REFERENCES users(id_user)
);

CREATE TABLE detail_peminjaman (
	id_detail INT AUTO_INCREMENT PRIMARY KEY,
	id_peminjaman INT NOT NULL,
	id_barang INT NOT NULL,
	jumlah INT NOT NULL,
	FOREIGN KEY(id_peminjaman) REFERENCES peminjaman(id_peminjaman),
	FOREIGN KEY(id_barang) REFERENCES barang(id_barang)
);

CREATE TABLE pengembalian (
	id_pengembalian INT AUTO_INCREMENT PRIMARY KEY,
	id_peminjaman INT NOT NULL UNIQUE,
	id_admin INT NOT NULL,
	tanggal_pengembalian DATETIME DEFAULT CURRENT_TIMESTAMP,
	kondisi_saat_kembali VARCHAR(100),
	catatan TEXT,
	FOREIGN KEY(id_peminjaman) REFERENCES peminjaman(id_peminjaman),
	FOREIGN KEY(id_admin) REFERENCES users(id_user)
);

INSERT INTO users(nama,email,password_hash,role)
VALUES('Admin Inventaris','admin@kantor.com','$2y$12$ub3PDKt2MK/5Z2dbpE/R5uV.rGy4aRC14BF97x1wauMxl0u1WQo8a','admin');

INSERT INTO kategori(nama_kategori,deskripsi)
VALUES
	('Elektronik','Laptop, proyektor, kamera'),
	('Alat Presentasi','Layar, pointer, whiteboard'),
	('Perlengkapan','Perlengkapan umum kantor');

INSERT INTO barang(id_kategori,kode_barang,nama_barang,jumlah_total,jumlah_tersedia,kondisi,lokasi)
VALUES
	(1,'ELK-001','Laptop',5,5,'Baik','Gudang A'),
	(1,'ELK-002','Proyektor',3,3,'Baik','Gudang A'),
	(2,'PRS-001','Layar Proyektor',2,2,'Baik','Gudang B'),
	(3,'UMM-001','Kabel Roll',10,10,'Baik','Gudang B');
