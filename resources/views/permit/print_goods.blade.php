<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $header->complain_no }}</title>
    {{-- Entry (I) / Exit (O) Permit of Goods mengikuti form kertas "SURAT IZIN KELUAR / MASUK BARANG". --}}
    <style type="text/css">
        @page { margin: 30px 46px 26px; }
        body { font-family: "DejaVu Sans", Helvetica, Arial, sans-serif; font-size: 8.5px; color: #000; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .center { text-align: center; }
        .title { font-size: 13px; font-weight: bold; }
        .strike { text-decoration: line-through; }

        .info td { padding: 3px 4px 2px; border-bottom: 1px solid #bfbfbf; }
        .info .lbl { width: 140px; white-space: nowrap; }
        .info .sep { width: 8px; padding-left: 0; padding-right: 0; }

        .grid th { background: #d9d9d9; border: 1px solid #000; padding: 3px 4px; font-weight: bold; }
        .grid td { border: 1px solid #000; padding: 1px 4px; height: 11px; }

        .fill td { padding: 2px 0; }
        .fill .lbl { width: 140px; }
        .fill .sep { width: 10px; }
        .fill .val { border-bottom: 1px solid #000; padding-left: 4px; }
        .fill .mid { text-align: right; padding-right: 4px; white-space: nowrap; }

        .rules ol { margin: 2px 0 0; padding-left: 36px; }
        .rules li { margin-bottom: 1px; line-height: 1.3; }

        .sign th, .sign td { border: 1px solid #000; padding: 3px; text-align: center; font-weight: normal; }
        .sign .space { height: 64px; vertical-align: bottom; }
    </style>
</head>
<body>

@php
    $txt = function ($value) {
        return trim((string) $value);
    };
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $idDate = function ($value, $withDay = false) use ($months, $days) {
        if (!$value) {
            return '';
        }
        $t = strtotime($value);
        return ($withDay ? $days[(int) date('w', $t)] . ', ' : '')
            . date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . date('Y', $t);
    };
    $period = function ($withDay = false) use ($header, $detail, $idDate) {
        $start = $header->start_date ?? ($detail->start_date ?? null);
        $end   = $header->end_date ?? ($detail->end_date ?? null);
        $text  = $idDate($start, $withDay);
        if ($end && date('Ymd', strtotime($end)) !== date('Ymd', strtotime($start))) {
            $text .= ' s/d ' . $idDate($end, $withDay);
        }
        return $text;
    };
    $time = function ($v) { return str_replace(':', '.', substr(trim((string) $v), 0, 5)); };
    $roman = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    // Tanggal surat: tanggal disetujui kalau ada, selain itu perubahan terakhir.
    $letterDate = ($header->date_approved ?? null) ?: $header->audit_date;
    $lt = strtotime($letterDate);

    // I = masuk (memasukkan), O = keluar (mengeluarkan); yang tidak berlaku dicoret.
    $out = $type === 'O';

    $floor = $txt($detail->floor ?? $header->floor);
    $unit  = $txt($detail->unit ?? $header->lot_no);
    $tower = $txt($detail->tower ?? '');

    // Tabel barang minimal 12 baris seperti form kertas.
    $rows = max(12, count($items));
@endphp

{{-- kop --}}
<table>
    <tr>
        <td style="width: 20%;"></td>
        <td class="center" style="width: 60%; vertical-align: bottom; padding-top: 30px;">
            <div class="title">SURAT IZIN KELUAR / MASUK BARANG</div>
            <div style="font-size: 9.5px; margin-top: 2px;">No : {{ $header->complain_no }} / SKMB / CEM-IFCA / {{ $roman[(int) date('n', $lt)] }} / {{ date('Y', $lt) }}</div>
        </td>
        <td class="center" style="width: 20%;">
            @if (is_file($logo))
                <img src="{{ $logo }}" style="width: 80px;">
            @endif
        </td>
    </tr>
</table>

<div style="margin-top: 12px;">
    Mohon diizinkan kepada pembawa surat ini
    <span class="{{ $out ? '' : 'strike' }}">mengeluarkan</span> / <span class="{{ $out ? 'strike' : '' }}">memasukkan</span> (*)
    barang untuk:
</div>

{{-- data pemohon --}}
<table class="info" style="margin-top: 10px;">
    <tr>
        <td class="lbl">Nama Pemohon</td><td class="sep">:</td>
        <td style="width: 28%;">{{ $txt($detail->member_name ?? $header->serv_req_by) }}</td>
        <td class="lbl" style="width: 100px;">Nama Pemilik / Penyewa</td><td class="sep">:</td>
        <td>{{ $txt($detail->owner_name ?? $header->owner_name) }}</td>
    </tr>
    <tr>
        <td class="lbl">Lantai &amp; No.Unit  / Tower</td><td class="sep">:</td>
        <td>{{ $floor }} &amp; {{ $unit }}{{ $tower !== '' ? ' / ' . $tower : '' }}</td>
        <td class="lbl">No. Telepon</td><td class="sep">:</td>
        <td>{{ $txt($detail->member_hp ?? $header->contact_no) }}</td>
    </tr>
    <tr>
        <td class="lbl">
            Tanggal <span class="{{ $out ? '' : 'strike' }}">Keluar</span> / <span class="{{ $out ? 'strike' : '' }}">Masuk</span> Barang
        </td><td class="sep">:</td>
        <td>{{ $period() }}</td>
        <td class="lbl">Jenis Pekerjaan</td><td class="sep">:</td>
        <td>{{ $txt($detail->work_type ?? $header->job_type) }}</td>
    </tr>
</table>

{{-- daftar barang --}}
<table class="grid" style="margin-top: 16px;">
    <tr>
        <th style="width: 46%;">JENIS BARANG</th>
        <th style="width: 20%;">JUMLAH</th>
        <th>KETERANGAN</th>
    </tr>
    @for ($i = 0; $i < $rows; $i++)
        <tr>
            <td>{{ $items[$i]['item_name'] ?? '' }}</td>
            <td class="center">{{ $items[$i]['item_qty'] ?? '' }}</td>
            <td>{{ $items[$i]['remarks'] ?? '' }}</td>
        </tr>
    @endfor
</table>

{{-- pengirim / pengambil --}}
<table class="fill" style="margin-top: 14px;">
    <tr>
        <td class="lbl">Nama / Pengirim / Pengambil</td><td class="sep">:</td>
        <td class="val" colspan="4">{{ $txt($detail->sender_name ?? '') }}</td>
    </tr>
    <tr>
        <td class="lbl">No. KTP / SIM</td><td class="sep">:</td>
        <td class="val" colspan="4">{{ $txt($detail->sender_id_no ?? '') }}</td>
    </tr>
    <tr>
        <td class="lbl">Alamat</td><td class="sep">:</td>
        <td class="val" colspan="4" style="height: 22px;">{{ $txt($detail->sender_address ?? '') }}</td>
    </tr>
    <tr>
        <td class="lbl">No. Telepon</td><td class="sep">:</td>
        <td class="val" colspan="4">{{ $txt($detail->sender_hp ?? '') }}</td>
    </tr>
    <tr>
        <td class="lbl">Hari / Tanggal</td><td class="sep">:</td>
        <td class="val" style="width: 42%;">{{ $period(true) }}</td>
        <td class="mid" style="width: 40px;">Jam :</td>
        <td class="val center">{{ $time($header->start_time) }}</td>
        <td class="val center" style="width: 18%;"><span style="float: left;">s.d.</span>{{ $time($header->end_time) }}</td>
    </tr>
    <tr>
        <td class="lbl">Jenis Kendaraan</td><td class="sep">:</td>
        <td class="val">{{ $txt($detail->vehicle_type ?? '') }}</td>
        <td class="mid" colspan="1">No Kendaraan :</td>
        <td class="val" colspan="2">{{ $txt($detail->vehicle_no ?? $header->vehicle_no) }}</td>
    </tr>
</table>

{{-- peraturan (teks tetap dari form kertas) --}}
<div class="rules" style="margin-top: 12px;">
    Tenant / Kontraktor harus memenuhi peraturan yang berlaku sbb :
    <ol>
        <li>Waktu memasukkan / mengeluarkan barang diizinkan pkl 22.00 &ndash; 10.00 wib kecuali ada izin khusus .<br>
            ( sebelum dan setelah selesai memasukkan / mengeluarkan barang harus melapor kepada security )</li>
        <li>Menjaga kebersihan pada saat dan setelah memasukkan / mengeluarkan barang .</li>
        <li>Bertanggung jawab atas segala akibat yang disebabkan oleh karyawan yang bersangkutan .</li>
        <li>Untuk tenant / unit yang tutup , kunci harap diserahkan kepada Badan Pengelola pada saat batas akhir tanggal masuk / keluar barang.</li>
        <li>Badan Pengelola berhak memberhentikan dan mencabut surat izin keluar masuk barang sewaktu-waktu jika terjadi pelanggaran tata tertib ini.</li>
    </ol>
</div>

<div style="margin-top: 2px;">Tangerang, {{ $idDate($letterDate) }}</div>

{{-- tanda tangan --}}
<table class="sign" style="margin-top: 10px;">
    <tr>
        <th rowspan="2" style="width: 21%; vertical-align: middle;">Pemohon</th>
        <th colspan="4" style="font-size: 9px;">Diperiksa &amp; Menyetujui</th>
    </tr>
    <tr>
        <th>Dept . Terkait</th>
        <th>General Service</th>
        <th>FA Manager</th>
        <th>Property Management</th>
    </tr>
    <tr>
        <td class="space">{{ $txt($detail->member_name ?? $header->serv_req_by) }}</td>
        <td class="space"></td>
        <td class="space"></td>
        <td class="space"></td>
        <td class="space"></td>
    </tr>
    <tr>
        <td style="text-align: left;">Nama dan Tanda Tangan</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>

{{-- pembagian rangkap (keterangan warna kertas) + garis hitam penutup --}}
<table style="margin-top: 8px; font-size: 7.5px;">
    <tr>
        <td style="width: 21%;">Putih : Pemohon</td>
        <td style="width: 19%;">Kuning : TRCS</td>
        <td style="width: 21%;">Hijau : Dept. Terkait</td>
        <td>Pink : General Service</td>
    </tr>
</table>
<div style="margin-top: 8px; font-size: 7px; font-style: italic;">(*) Coret yang tidak perlu</div>
<div style="margin-top: 2px; height: 11px; background: #000;"></div>

</body>
</html>
