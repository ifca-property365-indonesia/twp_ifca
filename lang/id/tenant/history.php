<?php

// Halaman riwayat tenant: Billing, Invoice, Overtime, Ticket History.
return [
    'billing_title'   => 'Riwayat Pembayaran',
    'invoice_title'   => 'Riwayat Tagihan',
    'overtime_title'  => 'Riwayat Lembur',
    'ticket_title'    => 'Riwayat Tiket',

    // filter
    'start_date_doc'  => 'Tanggal Mulai (Tanggal Dokumen)',
    'end_date_doc'    => 'Tanggal Akhir (Tanggal Dokumen)',

    // kolom tabel
    'col_no'          => 'No.',
    'document_number' => 'Nomor Dokumen',
    'doc_date'        => 'Tanggal Dokumen',
    'due_date'        => 'Jatuh Tempo',
    'description'     => 'Deskripsi',
    'period'          => 'Periode',
    'currency'        => 'Mata Uang',
    'amount'          => 'Jumlah',
    'paid'            => 'Lunas',
    'paid_date'       => 'Tanggal Lunas',
    'outstanding'     => 'Belum Dibayar',
    'id'              => 'ID',
    'request_date'    => 'Tanggal Permintaan',
    'unit'            => 'Unit',
    'start_overtime'  => 'Mulai Lembur',
    'end_overtime'    => 'Selesai Lembur',
    'ticket_number'   => 'Nomor Tiket',
    'category'        => 'Kategori',
    'reported_date'   => 'Tanggal Lapor',
    'request_by'      => 'Diminta Oleh',
    'lot_no'          => 'No. Lot',
    'ticket_status'   => 'Status Tiket',

    // status overtime (ot_trx.status)
    'overtime_statuses' => [
        'N'      => 'Menunggu persetujuan',
        'A'      => 'Disetujui',
        'X'      => 'Dibatalkan',
        'closed' => 'Sudah ditagih',
    ],

    // status ticket di luar common.statuses (kode dari sistem IFCA)
    'ticket_status_open' => 'Terbuka',
    'wo_number'          => 'No. WO',

    // pesan
    'search_error'    => ':status Pencarian : :error',
];
