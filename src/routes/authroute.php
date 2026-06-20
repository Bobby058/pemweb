<?php
// routes/authroute.php — pengganti authroute.js
// Berisi seluruh handler endpoint API + dispatcher sederhana (pengganti express.Router)

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/authMiddleware.php';
require_once __DIR__ . '/../utils/Jwt.php';
/**
 * Dispatcher: mencocokkan $method + $path dengan daftar route di bawah.
 * Dipanggil dari server.php untuk semua request yang diawali "/api".
 * Return true jika ada route yang cocok & sudah ditangani, false jika tidak ada yang cocok.
 */
function handleAuthRoutes(string $method, string $path): bool
{
    $pdo = getDB();

    // ===== AUTH =====
    if ($method === 'POST' && $path === '/auth/register') {
        registerHandler($pdo);
        return true;
    }
    if ($method === 'POST' && $path === '/auth/login') {
        loginHandler($pdo);
        return true;
    }
    if ($method === 'GET' && $path === '/auth/profile') {
        $user = authMiddleware();
        getProfileHandler($pdo, $user);
        return true;
    }
    if ($method === 'PUT' && $path === '/auth/profile') {
        $user = authMiddleware();
        updateProfileHandler($pdo, $user);
        return true;
    }

    // ===== BARANG =====
    if ($method === 'GET' && $path === '/barang') {
        authMiddleware();
        getBarangHandler($pdo);
        return true;
    }
    if ($method === 'POST' && $path === '/barang') {
        authMiddleware();
        createBarangHandler($pdo);
        return true;
    }
    if ($method === 'PUT' && preg_match('#^/barang/([^/]+)$#', $path, $m)) {
        authMiddleware();
        updateBarangHandler($pdo, $m[1]);
        return true;
    }
    if ($method === 'DELETE' && preg_match('#^/barang/([^/]+)$#', $path, $m)) {
        authMiddleware();
        deleteBarangHandler($pdo, $m[1]);
        return true;
    }

    // ===== INBOUND =====
    if ($method === 'GET' && $path === '/inbound') {
        authMiddleware();
        getInboundHandler($pdo);
        return true;
    }
    if ($method === 'POST' && $path === '/inbound') {
        authMiddleware();
        createInboundHandler($pdo);
        return true;
    }
    if ($method === 'DELETE' && preg_match('#^/inbound/([^/]+)$#', $path, $m)) {
        authMiddleware();
        deleteInboundHandler($pdo, $m[1]);
        return true;
    }

    // ===== OUTBOUND =====
    if ($method === 'GET' && $path === '/outbound') {
        authMiddleware();
        getOutboundHandler($pdo);
        return true;
    }
    if ($method === 'POST' && $path === '/outbound') {
        authMiddleware();
        createOutboundHandler($pdo);
        return true;
    }
    if ($method === 'DELETE' && preg_match('#^/outbound/([^/]+)$#', $path, $m)) {
        authMiddleware();
        deleteOutboundHandler($pdo, $m[1]);
        return true;
    }

    // ===== RETUR =====
    if ($method === 'GET' && $path === '/retur') {
        authMiddleware();
        getReturHandler($pdo);
        return true;
    }

    // ===== SUMMARY =====
    if ($method === 'GET' && $path === '/summary') {
        authMiddleware();
        getSummaryHandler($pdo);
        return true;
    }
    if ($method === 'GET' && $path === '/summary/chart') {
        authMiddleware();
        getSummaryChartHandler($pdo);
        return true;
    }

    return false;
}

// ================== HANDLERS ==================

function registerHandler(PDO $pdo): void
{
    $body = getJsonBody();
    $nama = $body['nama'] ?? null;
    $email = $body['email'] ?? null;
    $password = $body['password'] ?? null;
    $role = $body['role'] ?? 'staff';

    if (!$nama || !$email || !$password) {
        sendJson(['message' => 'Semua field wajib diisi'], 400);
        return;
    }

    try {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $email, $hash, $role]);
        sendJson(['message' => 'Registrasi berhasil'], 201);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            sendJson(['message' => 'Email sudah terdaftar'], 409);
            return;
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function loginHandler(PDO $pdo): void
{
    $body = getJsonBody();
    $email = $body['email'] ?? null;
    $password = $body['password'] ?? null;

    if (!$email || !$password) {
        sendJson(['message' => 'Email dan password wajib diisi'], 400);
        return;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(['message' => 'Email tidak ditemukan'], 401);
            return;
        }

        if (!password_verify($password, $user['password'])) {
            sendJson(['message' => 'Password salah'], 401);
            return;
        }

        $token = Jwt::sign(
            ['id' => $user['id'], 'nama' => $user['nama'], 'role' => $user['role']],
            getenv('JWT_SECRET') ?: '',
            8 * 3600
        );

        sendJson(['token' => $token, 'nama' => $user['nama'], 'role' => $user['role']]);
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function getProfileHandler(PDO $pdo, array $authUser): void
{
    try {
        $stmt = $pdo->prepare('SELECT id, nama, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$authUser['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(['message' => 'User tidak ditemukan'], 404);
            return;
        }
        sendJson($user);
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function updateProfileHandler(PDO $pdo, array $authUser): void
{
    $body = getJsonBody();
    $nama = $body['nama'] ?? null;
    $email = $body['email'] ?? null;
    $password = $body['password'] ?? null;

    try {
        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmt = $pdo->prepare('UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ?');
            $stmt->execute([$nama, $email, $hash, $authUser['id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET nama = ?, email = ? WHERE id = ?');
            $stmt->execute([$nama, $email, $authUser['id']]);
        }
        sendJson(['message' => 'Profil berhasil diupdate']);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            sendJson(['message' => 'Email sudah dipakai'], 409);
            return;
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function getBarangHandler(PDO $pdo): void
{
    try {
        $stmt = $pdo->query('SELECT * FROM barang ORDER BY nama ASC');
        sendJson($stmt->fetchAll());
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function createBarangHandler(PDO $pdo): void
{
    $body = getJsonBody();
    $kode = $body['kode'] ?? null;
    $nama = $body['nama'] ?? null;
    $kategori = $body['kategori'] ?? null;
    $satuan = $body['satuan'] ?? null;
    $stok = $body['stok'] ?? 0;
    $minStok = $body['min_stok'] ?? 5;

    if (!$kode || !$nama || !$kategori || !$satuan) {
        sendJson(['message' => 'Semua field wajib diisi'], 400);
        return;
    }

    try {
        $status = $stok === 0 ? 'habis' : ($stok <= $minStok ? 'menipis' : 'aman');
        $stmt = $pdo->prepare(
            'INSERT INTO barang (kode, nama, kategori, satuan, stok, min_stok, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$kode, $nama, $kategori, $satuan, $stok ?: 0, $minStok ?: 5, $status]);
        sendJson(['message' => 'Barang berhasil ditambahkan'], 201);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            sendJson(['message' => 'Kode barang sudah ada'], 409);
            return;
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function updateBarangHandler(PDO $pdo, string $kode): void
{
    $body = getJsonBody();
    $nama = $body['nama'] ?? null;
    $kategori = $body['kategori'] ?? null;
    $satuan = $body['satuan'] ?? null;
    $stok = $body['stok'] ?? null;
    $minStok = $body['min_stok'] ?? null;

    try {
        $status = $stok === 0 ? 'habis' : ($stok <= $minStok ? 'menipis' : 'aman');
        $stmt = $pdo->prepare(
            'UPDATE barang SET nama = ?, kategori = ?, satuan = ?, stok = ?, min_stok = ?, status = ? WHERE kode = ?'
        );
        $stmt->execute([$nama, $kategori, $satuan, $stok, $minStok, $status, $kode]);

        if ($stmt->rowCount() === 0) {
            sendJson(['message' => 'Barang tidak ditemukan'], 404);
            return;
        }
        sendJson(['message' => 'Barang berhasil diupdate']);
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function deleteBarangHandler(PDO $pdo, string $kode): void
{
    try {
        $stmt = $pdo->prepare('DELETE FROM barang WHERE kode = ?');
        $stmt->execute([$kode]);

        if ($stmt->rowCount() === 0) {
            sendJson(['message' => 'Barang tidak ditemukan'], 404);
            return;
        }
        sendJson(['message' => 'Barang berhasil dihapus']);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) === 1451) {
            sendJson(['message' => 'Barang tidak bisa dihapus karena masih ada di histori transaksi'], 409);
            return;
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function getInboundHandler(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("
            SELECT 
                i.id, i.tanggal,
                i.nomor_sj AS nomorSJ,
                i.supplier,
                i.kode_barang AS kodeBarang,
                i.jumlah, i.satuan,
                b.nama AS namaBarang
            FROM inbound i
            JOIN barang b ON i.kode_barang = b.kode
            ORDER BY i.tanggal DESC, i.created_at DESC
        ");
        sendJson($stmt->fetchAll());
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function createInboundHandler(PDO $pdo): void
{
    $body = getJsonBody();
    $tanggal = $body['tanggal'] ?? null;
    $nomorSJ = $body['nomorSJ'] ?? null;
    $supplier = $body['supplier'] ?? null;
    $kodeBarang = $body['kodeBarang'] ?? null;
    $jumlah = $body['jumlah'] ?? null;
    $satuan = $body['satuan'] ?? null;

    if (!$tanggal || !$nomorSJ || !$supplier || !$kodeBarang || !$jumlah) {
        sendJson(['message' => 'Semua field wajib diisi'], 400);
        return;
    }

    try {
        $pdo->beginTransaction();

        // pastikan barang ada dulu & ambil stok/min_stok saat ini
        $stmt = $pdo->prepare('SELECT stok, min_stok FROM barang WHERE kode = ?');
        $stmt->execute([$kodeBarang]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            sendJson(['message' => 'Barang tidak ditemukan'], 404);
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO inbound (tanggal, nomor_sj, supplier, kode_barang, jumlah, satuan) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tanggal, $nomorSJ, $supplier, $kodeBarang, $jumlah, $satuan]);

        // tambah stok barang & update status (aman/menipis/habis)
        $stokBaru = $row['stok'] + $jumlah;
        $status = $stokBaru <= 0 ? 'habis' : ($stokBaru <= $row['min_stok'] ? 'menipis' : 'aman');

        $stmt = $pdo->prepare('UPDATE barang SET stok = ?, status = ? WHERE kode = ?');
        $stmt->execute([$stokBaru, $status, $kodeBarang]);

        $pdo->commit();

        sendJson(['message' => 'Inbound berhasil ditambahkan'], 201);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function deleteInboundHandler(PDO $pdo, string $id): void
{
    try {
        $pdo->beginTransaction();

        // ambil data transaksi dulu supaya tahu kode barang & jumlahnya
        $stmt = $pdo->prepare('SELECT kode_barang, jumlah FROM inbound WHERE id = ?');
        $stmt->execute([$id]);
        $trx = $stmt->fetch();

        if (!$trx) {
            $pdo->rollBack();
            sendJson(['message' => 'Data tidak ditemukan'], 404);
            return;
        }

        $stmt = $pdo->prepare('DELETE FROM inbound WHERE id = ?');
        $stmt->execute([$id]);

        // kurangi balik stok barang (karena inbound-nya dibatalkan) & update status
        $stmt = $pdo->prepare('SELECT stok, min_stok FROM barang WHERE kode = ?');
        $stmt->execute([$trx['kode_barang']]);
        $barang = $stmt->fetch();

        if ($barang) {
            $stokBaru = max(0, $barang['stok'] - $trx['jumlah']);
            $status = $stokBaru <= 0 ? 'habis' : ($stokBaru <= $barang['min_stok'] ? 'menipis' : 'aman');

            $stmt = $pdo->prepare('UPDATE barang SET stok = ?, status = ? WHERE kode = ?');
            $stmt->execute([$stokBaru, $status, $trx['kode_barang']]);
        }

        $pdo->commit();

        sendJson(['message' => 'Inbound berhasil dihapus']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function getOutboundHandler(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("
            SELECT
                o.id, o.tanggal, o.kategori,
                o.kode_barang AS kodeBarang,
                o.jumlah, o.satuan,
                o.supplier,
                o.nomor_sj AS nomorSJ,
                o.keterangan,
                o.created_at,
                b.nama AS namaBarang
            FROM outbound o
            JOIN barang b ON o.kode_barang = b.kode
            ORDER BY o.tanggal DESC, o.created_at DESC
        ");
        sendJson($stmt->fetchAll());
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function createOutboundHandler(PDO $pdo): void
{
    $body = getJsonBody();
    $id = $body['id'] ?? null;
    $tanggal = $body['tanggal'] ?? null;
    $kategori = $body['kategori'] ?? null;
    // Catatan: form outbound.html mengirim field camelCase (kodeBarang, nomorSJ),
    // sedangkan kolom tabel "outbound" pakai snake_case (kode_barang, nomor_sj).
    // Terima kedua kemungkinan supaya datanya tetap tersimpan dengan benar.
    $kodeBarang = $body['kode_barang'] ?? $body['kodeBarang'] ?? null;
    $jumlah = $body['jumlah'] ?? null;
    $satuan = $body['satuan'] ?? null;
    $supplier = $body['supplier'] ?? null;
    $nomorSJ = $body['nomor_sj'] ?? $body['nomorSJ'] ?? null;
    $keterangan = $body['keterangan'] ?? null;

    if (!$tanggal || !$kategori || !$kodeBarang || !$jumlah) {
        sendJson(['message' => 'Semua field wajib diisi'], 400);
        return;
    }

    try {
        $pdo->beginTransaction();

        // cek stok cukup
        $stmt = $pdo->prepare('SELECT stok, min_stok FROM barang WHERE kode = ?');
        $stmt->execute([$kodeBarang]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            sendJson(['message' => 'Barang tidak ditemukan'], 404);
            return;
        }
        if ($row['stok'] < $jumlah) {
            $pdo->rollBack();
            sendJson(['message' => "Stok tidak cukup, stok saat ini: {$row['stok']}"], 400);
            return;
        }

        // generate id transaksi jika tidak dikirim dari frontend
        if (!$id) {
            $id = 'TRX-' . date('ymdHis') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO outbound (id, tanggal, kategori, kode_barang, jumlah, satuan, supplier, nomor_sj, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $tanggal, $kategori, $kodeBarang, $jumlah, $satuan, $supplier, $nomorSJ, $keterangan]);

        // kurangi stok barang & update status (aman/menipis/habis)
        $stokBaru = $row['stok'] - $jumlah;
        $status = $stokBaru <= 0 ? 'habis' : ($stokBaru <= $row['min_stok'] ? 'menipis' : 'aman');

        $stmt = $pdo->prepare('UPDATE barang SET stok = ?, status = ? WHERE kode = ?');
        $stmt->execute([$stokBaru, $status, $kodeBarang]);

        $pdo->commit();

        sendJson(['message' => 'Outbound berhasil ditambahkan'], 201);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            sendJson(['message' => 'ID transaksi sudah ada'], 409);
            return;
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function deleteOutboundHandler(PDO $pdo, string $id): void
{
    try {
        $pdo->beginTransaction();

        // ambil data transaksi dulu supaya tahu kode barang & jumlahnya
        $stmt = $pdo->prepare('SELECT kode_barang, jumlah FROM outbound WHERE id = ?');
        $stmt->execute([$id]);
        $trx = $stmt->fetch();

        if (!$trx) {
            $pdo->rollBack();
            sendJson(['message' => 'Data tidak ditemukan'], 404);
            return;
        }

        $stmt = $pdo->prepare('DELETE FROM outbound WHERE id = ?');
        $stmt->execute([$id]);

        // kembalikan stok barang (karena outbound-nya dibatalkan) & update status
        $stmt = $pdo->prepare('SELECT stok, min_stok FROM barang WHERE kode = ?');
        $stmt->execute([$trx['kode_barang']]);
        $barang = $stmt->fetch();

        if ($barang) {
            $stokBaru = $barang['stok'] + $trx['jumlah'];
            $status = $stokBaru <= 0 ? 'habis' : ($stokBaru <= $barang['min_stok'] ? 'menipis' : 'aman');

            $stmt = $pdo->prepare('UPDATE barang SET stok = ?, status = ? WHERE kode = ?');
            $stmt->execute([$stokBaru, $status, $trx['kode_barang']]);
        }

        $pdo->commit();

        sendJson(['message' => 'Outbound berhasil dihapus']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJson(['message' => 'Server error'], 500);
    }
}

function getReturHandler(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("
            SELECT
                o.id, o.tanggal, o.kategori,
                o.kode_barang AS kodeBarang,
                o.jumlah, o.satuan,
                o.supplier,
                o.nomor_sj AS nomorSJ,
                o.keterangan,
                o.created_at,
                b.nama AS namaBarang
            FROM outbound o
            JOIN barang b ON o.kode_barang = b.kode
            WHERE o.kategori = 'retur'
            ORDER BY o.tanggal DESC, o.created_at DESC
        ");
        sendJson($stmt->fetchAll());
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function getSummaryHandler(PDO $pdo): void
{
    try {
        $totalBarang = (int) $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
        $totalStok = (int) $pdo->query('SELECT COALESCE(SUM(stok), 0) FROM barang')->fetchColumn();
        $totalInbound = (int) $pdo->query('SELECT COALESCE(SUM(jumlah), 0) FROM inbound')->fetchColumn();
        $totalOutbound = (int) $pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM outbound WHERE kategori = 'pengiriman'")->fetchColumn();
        $stokMenipis = (int) $pdo->query('SELECT COUNT(*) FROM barang WHERE stok <= min_stok')->fetchColumn();

        $today = date('Y-m-d');

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(jumlah), 0) FROM inbound WHERE tanggal = ?');
        $stmt->execute([$today]);
        $inboundToday = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM outbound WHERE tanggal = ? AND kategori = 'pengiriman'");
        $stmt->execute([$today]);
        $outboundToday = (int) $stmt->fetchColumn();

        sendJson([
            'totalBarang'    => $totalBarang,
            'totalStok'      => $totalStok,
            'inboundCount'   => $totalInbound,
            'outboundCount'  => $totalOutbound,
            'inboundToday'   => $inboundToday,
            'outboundToday'  => $outboundToday,
            'lowStok'        => $stokMenipis,
        ]);
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}

function getSummaryChartHandler(PDO $pdo): void
{
    try {
        $inbound = $pdo->query("
            SELECT DATE_FORMAT(tanggal, '%b') AS bulan, SUM(jumlah) AS total
            FROM inbound
            GROUP BY DATE_FORMAT(tanggal, '%Y-%m'), DATE_FORMAT(tanggal, '%b')
            ORDER BY DATE_FORMAT(tanggal, '%Y-%m') ASC
            LIMIT 6
        ")->fetchAll();

        $outbound = $pdo->query("
            SELECT DATE_FORMAT(tanggal, '%b') AS bulan, SUM(jumlah) AS total
            FROM outbound WHERE kategori = 'pengiriman'
            GROUP BY DATE_FORMAT(tanggal, '%Y-%m'), DATE_FORMAT(tanggal, '%b')
            ORDER BY DATE_FORMAT(tanggal, '%Y-%m') ASC
            LIMIT 6
        ")->fetchAll();

        $kategori = $pdo->query("
            SELECT kategori, SUM(stok) AS total FROM barang GROUP BY kategori
        ")->fetchAll();

        $months = array_values(array_unique(array_merge(
            array_column($inbound, 'bulan'),
            array_column($outbound, 'bulan')
        )));

        $findTotal = function (array $rows, string $bulan) {
            foreach ($rows as $r) {
                if ($r['bulan'] === $bulan) return (int) $r['total'];
            }
            return 0;
        };

        $inboundByMonth = array_map(fn($m) => $findTotal($inbound, $m), $months);
        $outboundByMonth = array_map(fn($m) => $findTotal($outbound, $m), $months);

        sendJson([
            'months'          => array_values($months),
            'inboundByMonth'  => array_values($inboundByMonth),
            'outboundByMonth' => array_values($outboundByMonth),
            'kategoriLabels'  => array_column($kategori, 'kategori'),
            'kategoriValues'  => array_map('intval', array_column($kategori, 'total')),
        ]);
    } catch (PDOException $e) {
        sendJson(['message' => 'Server error'], 500);
    }
}
