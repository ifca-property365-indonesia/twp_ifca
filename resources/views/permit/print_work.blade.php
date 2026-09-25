<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $header->complain_no }}</title>
    {{-- Work Permit (W) mengikuti form kertas "SURAT IZIN KERJA / WORKING PERMIT". --}}
    <style type="text/css">
        @page { margin: 34px 46px 30px; }
        body { font-family: "DejaVu Sans", Helvetica, Arial, sans-serif; font-size: 8.5px; color: #000; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .center { text-align: center; }
        .title { font-size: 12px; font-weight: bold; text-decoration: underline; }
        .subtitle { font-size: 9px; font-weight: bold; font-style: italic; margin-top: 2px; }
        .docno { margin-top: 6px; }

        .info td { padding: 2px 0; }
        .info .lbl { width: 130px; }
        .info .sep { width: 14px; }
        .info .val { border-bottom: 1px solid #000; padding-left: 4px; }

        .grid th { background: #d9d9d9; border: 1px solid #000; padding: 2px 4px; font-weight: normal; }
        .grid td { border: 1px solid #000; padding: 1px 4px; height: 12px; }
        .grid .no { width: 22px; text-align: center; }
        .grid .foot td { border: 0; }
        .grid .foot td.total { border: 1px solid #000; }

        .notes { margin-top: 12px; }
        .notes ol { margin: 3px 0 0; padding-left: 26px; }
        .notes li { margin-bottom: 3px; line-height: 1.35; }

        .sign th, .sign td { border: 1px solid #000; padding: 4px; text-align: center; font-weight: normal; }
        .sign .space { height: 80px; vertical-align: bottom; }
    </style>
</head>
<body>

@php
    $txt = function ($value) {
        return trim((string) $value);
    };
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $idDate = function ($value) use ($months) {
        if (!$value) {
            return '';
        }
        $t = strtotime($value);
        return date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . date('Y', $t);
    };
    $roman = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    // Tanggal surat: tanggal disetujui kalau ada, selain itu perubahan terakhir.
    $letterDate = ($header->date_approved ?? null) ?: $header->audit_date;
    $lt = strtotime($letterDate);

    $area   = $txt($tenancy->project_desc ?? '') ?: 'IFCA';
    $floor  = $txt($detail->floor ?? $header->floor);
    $unit   = $txt($detail->unit ?? $header->lot_no);
    $tower  = $txt($detail->tower ?? '');
    $period = $idDate($header->start_date);
    if ($header->end_date && date('Ymd', strtotime($header->end_date)) !== date('Ymd', strtotime($header->start_date))) {
        $period .= ' s/d ' . $idDate($header->end_date);
    }
    $time = function ($v) { return str_replace(':', '.', substr(trim((string) $v), 0, 5)); };

    // Tabel kegiatan minimal 15 baris seperti form kertas.
    $rows = max(15, count($tools));
@endphp

{{-- kop --}}
<table>
    <tr>
        <td style="width: 22%;"></td>
        <td class="center" style="width: 56%; padding-top: 8px;">
            <div class="title">SURAT IZIN KERJA</div>
            <div class="subtitle">WORKING PERMIT</div>
            <div class="docno">NO : {{ $header->complain_no }} / SIK / CEM-IFCA / {{ $roman[(int) date('n', $lt)] }} / {{ date('Y', $lt) }}</div>
        </td>
        <td class="center" style="width: 22%;">
            @if (is_file($logo))
                <img src="{{ $logo }}" style="width: 92px;">
            @endif
        </td>
    </tr>
</table>

{{-- data pekerjaan --}}
<table class="info" style="width: 78%; margin-top: 4px;">
    <tr>
        <td class="lbl">Nama Tenant</td><td class="sep">:</td>
        <td class="val">{{ $txt($tenant->name ?? '') ?: $txt($header->debtor_acct) }}</td>
    </tr>
    <tr>
        <td class="lbl">Lantai &amp; No . Unit / Tower</td><td class="sep">:</td>
        <td class="val">{{ $floor }} &amp; {{ $unit }}{{ $tower !== '' ? ' / ' . $tower : '' }}</td>
    </tr>
    <tr>
        <td class="lbl">Nama Kontraktor</td><td class="sep">:</td>
        <td class="val">{{ $txt($detail->kontraktor_name ?? $header->contractor) }}</td>
    </tr>
    <tr>
        <td class="lbl">Nama Penanggung Jawab</td><td class="sep">:</td>
        <td class="val">{{ $txt($detail->pic_name ?? $header->pj_name) }}</td>
    </tr>
    <tr>
        <td class="lbl">Telp. Kantor / HP</td><td class="sep">:</td>
        <td class="val">{{ $txt($detail->pic_hp ?? '') }}</td>
    </tr>
    <tr>
        <td class="lbl">Jenis Pekerjaan</td><td class="sep">:</td>
        <td class="val">{{ $txt($detail->work_type ?? $header->job_type) }}</td>
    </tr>
    <tr>
        <td class="lbl">Tanggal Kerja</td><td class="sep">:</td>
        <td class="val">{{ $period }}</td>
    </tr>
    <tr>
        <td class="lbl">Jam Kerja</td><td class="sep">:</td>
        <td class="val">Pkl {{ $time($header->start_time) }} - {{ $time($header->end_time) }}</td>
    </tr>
</table>

<div style="margin-top: 6px;">Dengan ini diberikan izin pekerjaan di area {{ $area }} , dengan rincian sebagai berikut :</div>

{{-- rincian kegiatan & peralatan --}}
<table class="grid" style="margin-top: 1px;">
    <tr>
        <th class="no">No.</th>
        <th style="width: 42%;">Jenis Pekerjaan / Kegiatan</th>
        <th style="width: 27%;">Peralatan / Alat Pelindung Diri</th>
        <th>Keterangan</th>
    </tr>
    @for ($i = 0; $i < $rows; $i++)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $tools[$i]['activity'] ?? '' }}</td>
            <td>{{ $tools[$i]['tool_name'] ?? '' }}</td>
            <td>{{ $tools[$i]['remarks'] ?? '' }}</td>
        </tr>
    @endfor
    <tr class="foot">
        <td></td>
        <td class="total">
            <table><tr>
                <td style="border: 0; padding: 0; height: auto;">Jumlah Pekerja : &nbsp; {{ $workers }}</td>
                <td style="border: 0; padding: 0 30px 0 0; height: auto; text-align: right;">Orang</td>
            </tr></table>
        </td>
        <td></td>
        <td></td>
    </tr>
</table>

{{-- catatan (teks tetap dari form kertas) --}}
<div class="notes">
    <strong><em>Catatan :</em></strong>
    <ol>
        <li>Setiap pekerjaan di dalam area/gedung, wajib mengisi dan menempelkan formulir yang telah ditanda tangani ini didepan pintu masuk unit.</li>
        <li>Setiap pekerja diwajibkan membawa peralatan safety yang sesuai dengan bidang pekerjaannya. Untuk itu, setiap kontraktor
            diminta untuk menanyakan / mendapatkan informasi jelas mengenai Standart of Safety Prosedure dari Departement HSE.
            Building Management berhak menghentikan pekerjaan apabila tidak melaksanakan prosedur Safety selama bekerja di lingkungan gedung.</li>
        <li>Setiap pekerja wajib menggunakan kartu pengenal (No ID, No Entry) di area {{ $area }} .</li>
        <li>Puing dan Sampah setiap pekerjaan harus dibungkus dan dibuang keluar area {{ $area }} .</li>
        <li>Waktu pengurusan izin kerja adalah pada hari kerja 09.00 - 17.00 ( Senin - Jumat ) di Badan Pengelola lantai 3 .</li>
        <li>Badan Pengelola berhak memberhentikan dan mencabut surat izin kerja ini sewaktu - waktu jika tenant / kontraktor melanggar tata tertib kerja .</li>
    </ol>
</div>

<div style="margin-top: 10px;">Tangerang , {{ $idDate($letterDate) }}</div>

{{-- tanda tangan --}}
<table class="sign" style="margin-top: 2px;">
    <tr>
        <th rowspan="2" style="width: 18%; vertical-align: middle;">Pemohon</th>
        <th colspan="4">Diperiksa &amp; Menyetujui</th>
    </tr>
    <tr>
        <th>Dept. Terkait</th>
        <th>Engineering</th>
        <th>General Service</th>
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
        <td style="font-size: 7.5px;">Nama dan Tanda Tangan</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>

{{-- pembagian rangkap (keterangan warna kertas) + garis hitam penutup --}}
<table style="margin-top: 8px; font-style: italic; font-size: 7.5px;">
    <tr>
        <td style="width: 19%; padding-left: 6px;">Putih : Pemohon</td>
        <td style="width: 21%;">Kuning : TRCS</td>
        <td style="width: 28%;">Hijau : Engineering</td>
        <td>Pink : General Service</td>
    </tr>
</table>
<div style="margin-top: 10px; height: 13px; background: #000;"></div>

</body>
</html>
