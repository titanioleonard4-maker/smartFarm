<?php
session_start();
if (!isset($_SESSION['id_peternak'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$id_sesi  = (int) $_SESSION['id_peternak'];

$where_role = $is_admin
    ? "WHERE p.role = 'peternak'"
    : "WHERE p.role = 'peternak' AND p.id_peternak = $id_sesi";

$query = "SELECT 
    p.id_peternak,
    p.nama_peternak, 
    COUNT(h.id_hewan) as total_hewan,
    SUM(CASE WHEN h.jenis_kelamin = 'Jantan' THEN 1 ELSE 0 END) as total_jantan,
    SUM(CASE WHEN h.jenis_kelamin = 'Betina' THEN 1 ELSE 0 END) as total_betina
FROM peternak p
LEFT JOIN hewan h ON p.id_peternak = h.id_peternak
$where_role
GROUP BY p.id_peternak";

$result       = $conn->query($query)->fetchAll();
$peternak_data    = [];
$label_peternak   = [];
$data_total_hewan = [];

foreach ($result as $row) {
    if ((int)$row['total_hewan'] === 0) continue;
    $peternak_data[]    = $row;
    $label_peternak[]   = $row['nama_peternak'];
    $data_total_hewan[] = (int)$row['total_hewan'];
}

$where_map = $is_admin ? "" : "WHERE id_peternak = $id_sesi";
$hewan_map = [];
foreach ($conn->query("SELECT id_hewan, id_peternak FROM hewan $where_map")->fetchAll() as $m) {
    $hewan_map[$m['id_hewan']] = $m['id_peternak'];
}

$hewan_map_all = [];
foreach ($conn->query("SELECT id_hewan, id_peternak FROM hewan")->fetchAll() as $m) {
    $hewan_map_all[$m['id_hewan']] = $m['id_peternak'];
}

$all_peternak_data  = [];
$all_label_peternak = [];
$query_all = "
    SELECT p.id_peternak, p.nama_peternak, COUNT(h.id_hewan) as total_hewan
    FROM peternak p
    LEFT JOIN hewan h ON p.id_peternak = h.id_peternak
    WHERE p.role = 'peternak'
    GROUP BY p.id_peternak
    HAVING COUNT(h.id_hewan) > 0
";
foreach ($conn->query($query_all)->fetchAll() as $row) {
    $all_peternak_data[]  = $row;
    $all_label_peternak[] = $row['nama_peternak'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Smart Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <style>
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column-reverse;
            gap: 8px;
            max-width: 350px;
        }
    </style>
</head>
<body data-bs-theme="dark" class="bg-dark text-light">

    <div id="notif-global" class="alert alert-danger alert-dismissible fade d-none m-0 rounded-0" role="alert" style="position:sticky; top:0; z-index:9999;">
        <strong>Peringatan!</strong> <span id="notif-pesan"></span>
        <button type="button" class="btn-close" onclick="tutupNotif()"></button>
    </div>

    <div class="container mt-4">
        <button class="btn btn-outline-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            Menu
        </button>
        <button id="btn-notif" class="btn btn-outline-secondary btn-sm ms-2" onclick="toggleNotif()">
            Notifikasi: <span id="notif-status">...</span>
        </button>
    </div>

    <div class="offcanvas offcanvas-start bg-dark border-end border-success" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title text-success fw-bold">Smart Farm</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent"><a href="dashboard.php" class="text-decoration-none text-success fw-bold">Dashboard Utama</a></li>
                <?php if ($is_admin) : ?>
                    <li class="list-group-item bg-transparent"><a href="tambah_hewan.php" class="text-decoration-none text-light">Tambah Hewan</a></li>
                    <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-light">Tambah Peternak</a></li>
                    <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-light">Data Peternak</a></li>
                <?php endif; ?>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-light">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-light">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <h2 class="mb-4 text-success fw-bold">Dashboard Pemantauan</h2>

        <div class="row g-4 mb-5">
            <?php foreach ($peternak_data as $row): ?>
            <div class="col-md-4">
                <div class="card bg-dark border-secondary shadow">
                    <div id="header-peternak-<?= $row['id_peternak'] ?>" class="card-header bg-secondary text-white fw-bold">
                        Peternakan: <?= htmlspecialchars($row['nama_peternak']) ?>
                    </div>
                    <div class="card-body">
                        <h1 class="display-5 text-center text-light mb-3">
                            <?= $row['total_hewan'] ?: 0 ?> <span class="fs-5 text-muted">Ekor</span>
                        </h1>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                                <span>Jantan:</span> <strong><?= $row['total_jantan'] ?: 0 ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                                <span>Betina:</span> <strong><?= $row['total_betina'] ?: 0 ?></strong>
                            </li>
                        </ul>
                        <div id="alert-peternak-<?= $row['id_peternak'] ?>" class="alert bg-secondary text-white text-center m-0 p-2 fw-bold">
                            <?php if ($row['total_hewan'] == 0): ?>
                                Belum Ada Hewan
                            <?php else: ?>
                                Menunggu data sensor...
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card bg-dark border-secondary p-3">
                    <h5 class="text-center text-light">Populasi per Peternak</h5>
                    <canvas id="chartPopulasi"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-dark border-secondary p-3">
                    <h5 class="text-center text-light">Rasio Kesehatan (Live)</h5>
                    <canvas id="chartKesehatan"></canvas>
                    <div class="d-flex justify-content-center gap-4 mt-2">
                        <span class="text-success fw-bold">Sehat: <span id="label-sehat-global">0</span></span>
                        <span class="text-danger fw-bold">Tidak Normal: <span id="label-sakit-global">0</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const peternakData    = <?= json_encode($peternak_data) ?>;
        const hewanMap        = <?= json_encode($hewan_map) ?>;
        const hewanMapAll     = <?= json_encode($hewan_map_all) ?>;
        const allPeternakData = <?= json_encode($all_peternak_data) ?>;

        const labelPopulasi = <?= json_encode($label_peternak) ?>;
        const dataPopulasi  = <?= json_encode($data_total_hewan) ?>;

        const barColors       = dataPopulasi.map(() => 'rgba(25, 135, 84, 0.7)');
        const barBorderColors = dataPopulasi.map(() => 'rgba(25, 135, 84, 1)');

        const ctxPopulasi = document.getElementById('chartPopulasi').getContext('2d');
        const chartPopulasi = new Chart(ctxPopulasi, {
            type: 'bar',
            data: {
                labels: labelPopulasi,
                datasets: [{
                    label: 'Jumlah Sapi',
                    data: dataPopulasi,
                    backgroundColor: barColors,
                    borderColor: barBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, ticks: { color: 'white' }, grid: { color: '#444' } },
                    x: { ticks: { color: 'white' } }
                },
                plugins: { legend: { labels: { color: 'white' } } }
            }
        });

        const ctxKesehatan = document.getElementById('chartKesehatan').getContext('2d');
        const chartKesehatan = new Chart(ctxKesehatan, {
            type: 'doughnut',
            data: {
                labels: ['Sehat', 'Tidak Normal'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['rgba(25, 135, 84, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { labels: { color: 'white' } } } }
        });

        let notifAktif = localStorage.getItem('notif_aktif') !== 'false';

        function updateTombolNotif() {
            const status = document.getElementById('notif-status');
            const btn    = document.getElementById('btn-notif');
            if (notifAktif) {
                status.textContent = 'ON';
                btn.className = 'btn btn-success btn-sm ms-2';
            } else {
                status.textContent = 'OFF';
                btn.className = 'btn btn-outline-secondary btn-sm ms-2';
            }
        }

        function toggleNotif() {
            notifAktif = !notifAktif;
            localStorage.setItem('notif_aktif', String(notifAktif));
            updateTombolNotif();
            if (notifAktif && Notification.permission === 'default') Notification.requestPermission();
            if (notifAktif && Notification.permission === 'denied') alert('Notifikasi diblokir browser.');
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateTombolNotif();
            if (notifAktif && Notification.permission === 'default') Notification.requestPermission();
        });

        const NOTIF_TAG = 'smartfarm-alert';
        const maxToast  = 5;

        function tampilkanToast(judul, pesan) {
            if (!notifAktif) return;
            const container = document.getElementById('toast-container');
            const existing  = container.querySelectorAll('.toast');
            if (existing.length >= maxToast) existing[existing.length - 1].remove();
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-stretch text-white bg-dark border border-danger show';
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
                <div class="d-flex flex-column w-100">
                    <div class="toast-header bg-danger text-white border-0">
                        <strong class="me-auto">${judul}</strong>
                        <button type="button" class="btn-close btn-close-white ms-2"
                            onclick="this.closest('.toast').remove()"></button>
                    </div>
                    <div class="toast-body text-secondary">${pesan}</div>
                </div>`;
            container.prepend(toastEl);
            setTimeout(() => { toastEl.classList.remove('show'); setTimeout(() => toastEl.remove(), 300); }, 5000);
        }

        function tampilkanNotifOS(jumlahSakit) {
            if (!notifAktif || Notification.permission !== 'granted') return;
            new Notification('Peringatan - Smart Farm', {
                body: `${jumlahSakit} sapi terdeteksi tidak normal.`,
                icon: 'favicon.ico',
                tag: NOTIF_TAG,
                renotify: true
            });
        }

        let statusSebelumnya = {};
        let jumlahAbnormalSebelumnya = 0;
        let hewanTidakNormal = {};
        let globalHewanTidakNormal = {};

        function cekNormal(suhu, detak) { return suhu <= 39 && detak >= 55 && detak <= 100; }

        function updateNotifGlobal() {
            const jumlah = Object.values(hewanTidakNormal).filter(Boolean).length;
            const notif  = document.getElementById('notif-global');
            const pesan  = document.getElementById('notif-pesan');
            if (jumlah > 0) {
                pesan.textContent = jumlah + ' hewan terdeteksi tidak normal. Segera cek kondisi ternak!';
                notif.classList.remove('d-none');
                notif.classList.add('show');
            } else {
                notif.classList.add('d-none');
                notif.classList.remove('show');
            }
        }

        function tutupNotif() { document.getElementById('notif-global').classList.add('d-none'); }

        function updateChartPopulasi() {
            peternakData.forEach((p, idx) => {
                const statusHewan  = Object.entries(hewanTidakNormal)
                    .filter(([id_hewan]) => hewanMap[id_hewan] == p.id_peternak)
                    .map(([, sakit]) => sakit);
                const adaYangSakit = statusHewan.some(sakit => sakit);
                const belumAda     = statusHewan.length === 0;
                const warna = (!belumAda && adaYangSakit) ? 'rgba(220, 53, 69' : 'rgba(25, 135, 84';
                chartPopulasi.data.datasets[0].backgroundColor[idx] = warna + ', 0.7)';
                chartPopulasi.data.datasets[0].borderColor[idx]      = warna + ', 1)';
            });
            chartPopulasi.update();
        }

        function updateChartKesehatan() {
            let sehat = 0, sakit = 0;
            Object.values(globalHewanTidakNormal).forEach(v => v ? sakit++ : sehat++);
            chartKesehatan.data.datasets[0].data = [sehat, sakit];
            chartKesehatan.update();
            document.getElementById('label-sehat-global').textContent = sehat;
            document.getElementById('label-sakit-global').textContent = sakit;
        }

        const socket = io("http://localhost:3000");
        const idPeternak = <?= json_encode($_SESSION['id_peternak']) ?>;
        const isAdmin    = <?= json_encode($is_admin) ?>;
        socket.on("connect", () => { socket.emit("join_room", { id_peternak: idPeternak, is_admin: isAdmin }); });

        let updateBuffer  = {};
        let globalBuffer  = {};
        let bufferTimeout = null;

        socket.on("update_suhu", (data) => {
            globalBuffer[data.id_hewan] = data;
            if (data.id_hewan in hewanMap) updateBuffer[data.id_hewan] = data;

            clearTimeout(bufferTimeout);
            bufferTimeout = setTimeout(() => {
                let jumlahSakitBaru = 0;
                let adaPerubahan    = false;

                Object.values(updateBuffer).forEach(d => {
                    const normal        = cekNormal(d.suhu, d.detak_jantung);
                    const wasNormal     = statusSebelumnya[d.id_hewan];
                    const statusBerubah = (wasNormal === undefined) || (wasNormal !== normal);
                    if (!normal) jumlahSakitBaru++;
                    if (statusBerubah) adaPerubahan = true;
                    statusSebelumnya[d.id_hewan] = normal;
                    hewanTidakNormal[d.id_hewan] = !normal;

                    const id_p    = hewanMap[d.id_hewan];
                    const alertEl = document.getElementById('alert-peternak-' + id_p);
                    const headerEl = document.getElementById('header-peternak-' + id_p);
                    if (alertEl) {
                        alertEl.className   = normal ? 'alert alert-success text-center m-0 p-2 fw-bold' : 'alert alert-danger text-center m-0 p-2 fw-bold';
                        alertEl.textContent = normal ? 'Semua ternak normal' : `Hewan #${d.id_hewan} tidak normal! Suhu: ${d.suhu}°C · ${d.detak_jantung} bpm`;
                    }
                    if (headerEl) {
                        const nama = peternakData.find(p => p.id_peternak == id_p)?.nama_peternak ?? '';
                        headerEl.className   = normal ? 'card-header bg-success text-white fw-bold' : 'card-header bg-danger text-white fw-bold';
                        headerEl.textContent = 'Peternakan: ' + nama;
                    }
                });

                if (jumlahSakitBaru > 0 && (adaPerubahan || jumlahSakitBaru !== jumlahAbnormalSebelumnya)) {
                    tampilkanToast('Peringatan - Smart Farm', `${jumlahSakitBaru} sapi tidak normal. Segera cek kondisi ternak.`);
                    tampilkanNotifOS(jumlahSakitBaru);
                }
                jumlahAbnormalSebelumnya = jumlahSakitBaru;
                updateBuffer = {};
                updateNotifGlobal();
                updateChartPopulasi();

                Object.values(globalBuffer).forEach(d => {
                    globalHewanTidakNormal[d.id_hewan] = !cekNormal(d.suhu, d.detak_jantung);
                });
                globalBuffer = {};
                updateChartKesehatan();
            }, 1000);
        });
    </script>
</body>
</html>