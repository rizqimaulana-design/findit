<?php
function kirimWA($nomor, $pesan) {
    // Bersihkan nomor — hapus +, spasi, strip
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    // Ganti awalan 0 jadi 62 (format internasional)
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    if (empty($nomor) || strlen($nomor) < 10) {
        return false;
    }

    $data = [
        'target'  => $nomor,
        'message' => $pesan,
    ];

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . FONNTE_TOKEN
        ]
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return false;
    }

    $hasil = json_decode($response, true);
    return isset($hasil['status']) && $hasil['status'] === true;
}

function pesanLaporanBaru($nama_barang, $type, $lokasi, $pelapor) {
    $jenis = $type === 'hilang' ? 'HILANG' : 'TEMUAN';
    return "🔔 *FindIt Campus*\n\n"
         . "Ada laporan barang *{$jenis}* baru!\n\n"
         . "📦 Barang: {$nama_barang}\n"
         . "📍 Lokasi: {$lokasi}\n"
         . "👤 Pelapor: {$pelapor}\n\n"
         . "Segera cek di: http://localhost/findit";
}

function pesanStatusUpdate($nama_barang, $status, $kontak_admin = '') {
    $status_text = match($status) {
        'terverifikasi' => '✅ Terverifikasi',
        'selesai'       => '🎉 Selesai / Dikembalikan',
        default         => ucfirst($status)
    };

    return "🔔 *FindIt Campus*\n\n"
         . "Update laporan barang kamu!\n\n"
         . "📦 Barang: {$nama_barang}\n"
         . "📋 Status: {$status_text}\n\n"
         . ($kontak_admin ? "📞 Hubungi admin: {$kontak_admin}\n\n" : '')
         . "Cek detail: http://localhost/findit";
}
?>