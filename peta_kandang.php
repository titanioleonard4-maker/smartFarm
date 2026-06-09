<?php
session_start();
if (!isset($_SESSION['id_peternak'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');
$id_sesi  = (int) $_SESSION['id_peternak'];

if ($is_admin) {
    $stmt = $conn->query(
        "SELECT h.id_hewan, h.kode_kandang, k.posisi_x, k.posisi_y 
         FROM hewan h 
         JOIN kandang k ON h.kode_kandang = k.kode_kandang"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT h.id_hewan, h.kode_kandang, k.posisi_x, k.posisi_y 
         FROM hewan h 
         JOIN kandang k ON h.kode_kandang = k.kode_kandang 
         WHERE h.id_peternak = :id"
    );
    $stmt->execute([':id' => $id_sesi]);
}

$hewan_awal = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['suhu']          = 37.5;
    $row['detak_jantung'] = 72;
    $hewan_awal[]         = $row;
}

if ($is_admin) {
    $stmt_map = $conn->query("SELECT id_hewan FROM hewan");
} else {
    $stmt_map = $conn->prepare("SELECT id_hewan FROM hewan WHERE id_peternak = :id");
    $stmt_map->execute([':id' => $id_sesi]);
}

$hewan_boleh = [];
while ($m = $stmt_map->fetch(PDO::FETCH_ASSOC)) {
    $hewan_boleh[] = (int) $m['id_hewan'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peta Kandang - Smart Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <style>
        #map { height: 550px; width: 100%; border: 2px solid #198754; border-radius: 8px; }
        .sidebar-list { height: 550px; overflow-y: auto; }
        .custom-icon { background: none; border: none; }
    </style>
</head>
<body data-bs-theme="dark" class="bg-dark text-light">

    <div class="container mt-4">
        <button class="btn btn-outline-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            Menu
        </button>
    </div>

    <div class="offcanvas offcanvas-start bg-dark border-end border-success" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title text-success fw-bold">Smart Farm</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent"><a href="dashboard.php" class="text-decoration-none text-light">Dashboard Utama</a></li>
                <?php if ($is_admin) : ?>
                    <li class="list-group-item bg-transparent"><a href="tambah_hewan.php" class="text-decoration-none text-light">Tambah Hewan</a></li>
                    <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-light">Tambah Peternak</a></li>
                    <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-light">Data Peternak</a></li>
                <?php endif; ?>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-light">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-success fw-bold">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container-fluid p-4">
        <h2 class="text-success fw-bold mb-4">Peta Kandang & Status Hewan</h2>
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card bg-dark border-secondary p-2 shadow-lg">
                    <div id="map"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark border-secondary shadow-lg">
                    <div class="card-header bg-transparent border-secondary text-success fw-bold">Daftar Kondisi Ternak</div>
                    <div class="card-body p-0 sidebar-list" id="animal-list-group"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var map = L.map('map', { crs: L.CRS.Simple, minZoom: -1 });
        var bounds = [[0,0], [1100, 1900]];
        var image = L.imageOverlay('denah_kandang.png', bounds).addTo(map);
        map.fitBounds(bounds);

        var hewanMarkers = {};

        var hewanBoleh = new Set(<?= json_encode($hewan_boleh) ?>);

        function updateKondisiHewan(id, kode_kandang, x, y, suhu, detak) {
            var suhuAbnormal  = suhu > 39;
            var detakAbnormal = detak < 55 || detak > 100;
            var alert         = suhuAbnormal || detakAbnormal;
            var statusColor   = alert ? '#dc3545' : '#198754';
            var textClass     = alert ? 'text-danger' : 'text-success';

            var iconHTML = '<div style="display:flex; align-items:center; gap:3px;">'
                + '<div style="background-color:' + statusColor + '; width:10px; height:10px; border-radius:50%; border:2px solid white; flex-shrink:0;"></div>'
                + '<span style="background:#212529; border:1px solid #444; border-radius:4px; padding:1px 5px; font-size:10px; color:#fff; white-space:nowrap;">'
                + suhu + '\u00b0C \u00b7 ' + detak + 'bpm'
                + '</span>'
                + '</div>';

            var customIcon = L.divIcon({
                className: 'custom-icon',
                html: iconHTML,
                iconSize: [100, 18],
                iconAnchor: [8, 9]
            });

            var popupContent = '<b>Kandang: ' + kode_kandang + '</b><br>'
                + 'ID Hewan: #' + id + '<br>'
                + 'Suhu: <b>' + suhu + '\u00b0C</b><br>'
                + 'Detak Jantung: <b>' + detak + ' bpm</b>';

            if (hewanMarkers[id]) {
                hewanMarkers[id].setLatLng([y, x]).setIcon(customIcon);
                hewanMarkers[id].getPopup().setContent(popupContent);
            } else {
                hewanMarkers[id] = L.marker([y, x], {icon: customIcon})
                    .addTo(map)
                    .bindPopup(popupContent);
            }

            var listContainer = document.getElementById('animal-list-group');
            var existing      = document.getElementById('list-' + id);
            var itemHTML = '<div class="d-flex justify-content-between align-items-center w-100 p-3">'
                + '<div>'
                + '<h6 class="mb-0 text-white">#' + id + ' <span class="badge bg-success ms-1">' + kode_kandang + '</span></h6>'
                + '<small>Suhu: <b class="' + textClass + '">' + suhu + '\u00b0C</b></small><br>'
                + '<small>Detak: <b class="' + textClass + '">' + detak + ' bpm</b></small>'
                + '</div>'
                + '<div style="background-color:' + statusColor + '; width:14px; height:14px; border-radius:50%; border:2px solid white;"></div>'
                + '</div>';

            if (existing) {
                existing.innerHTML = itemHTML;
            } else {
                var div = document.createElement('div');
                div.id = 'list-' + id;
                div.className = 'border-bottom border-secondary animal-item';
                div.setAttribute('data-id', id);
                div.setAttribute('data-kandang', kode_kandang);
                div.innerHTML = itemHTML;
                listContainer.appendChild(div);
            }

            var items = Array.from(listContainer.getElementsByClassName('animal-item'));
            items.sort(function(a, b) {
                var ka = a.getAttribute('data-kandang');
                var kb = b.getAttribute('data-kandang');
                if (ka[0] !== kb[0]) return ka[0].localeCompare(kb[0]);
                return parseInt(ka.slice(1)) - parseInt(kb.slice(1));
            });
            items.forEach(function(item) { listContainer.appendChild(item); });
        }

        var dataAwal = <?= json_encode($hewan_awal) ?>;
        dataAwal.forEach(function(h) {
            updateKondisiHewan(h.id_hewan, h.kode_kandang, h.posisi_x, h.posisi_y, h.suhu, h.detak_jantung);
        });

        var socket = io('http://localhost:3000');
        var idPeternak = <?= json_encode($_SESSION['id_peternak']) ?>;
        var isAdmin    = <?= json_encode($is_admin) ?>;
        socket.on('connect', function() {
            socket.emit('join_room', { id_peternak: idPeternak, is_admin: isAdmin });
        });

        socket.on('update_suhu', function(data) {
            if (!hewanBoleh.has(data.id_hewan)) return;
            updateKondisiHewan(data.id_hewan, data.kode_kandang, data.pos_x, data.pos_y, data.suhu, data.detak_jantung);
        });
    </script>
</body>
</html>
