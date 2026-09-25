<?php

// Admin: Dashboard (admin/dash) & ekspor PDF pemakaian listrik.
return [
    'title'            => 'Dasbor',
    'heading'          => 'Dasbor Administrator',
    'welcome'          => 'Selamat datang di Portal Web Tenant.',
    'work_order_graphic' => 'Grafik Work Order',
    'utility_usage'    => 'Pemakaian Utilitas',
    'water'            => 'Air',
    'gas'              => 'Gas',
    'electric'         => 'Listrik',
    'ticket'           => 'Tiket',
    'col_no'           => 'No.',
    'ticket_number'    => 'Nomor Tiket',
    'wo_number'        => 'No. WO',
    'tenant_name'      => 'Nama Tenant',
    'reported_date'    => 'Tanggal Lapor',
    'request_by'       => 'Diminta Oleh',
    'ticket_status'    => 'Status Tiket',
    'lwbp_label'       => 'LWBP (Lewat Waktu Beban Puncak) 22:00 - 17:00',
    'wbp_label'        => 'WBP (Waktu Beban Puncak) 17:00 - 22:00',
    'usage'            => 'Pemakaian',

    // baris grafik status work order (kunci = kode baris, jangan diubah)
    'wo_status' => [
        'Submit'    => 'Dikirim',
        'Open'      => 'Terbuka',
        'Assigned'  => 'Ditugaskan',
        'Process'   => 'Diproses',
        'Confirm'   => 'Konfirmasi',
        'Closed'    => 'Selesai',
        'Cancelled' => 'Dibatalkan',
        'Total'     => 'Total',
    ],

    // label status ticket di tabel dashboard (kunci = kode status)
    'ticket_statuses' => [
        'O' => 'Terbuka',
        'R' => 'Dikirim',
        'A' => 'Ditugaskan',
        'Y' => 'Disetujui',
        'C' => 'Selesai',
        'X' => 'Dibatalkan',
        'S' => 'Survei',
        'P' => 'Diproses',
        'M' => 'Diubah',
        'Z' => 'Disetujui (Berbayar)',
        'F' => 'Konfirmasi',
    ],

    'months_short' => [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ],

    // PDF
    'pdf_heading'      => 'Ringkasan Pemakaian Listrik',
    'pdf_lwbp'         => 'Pemakaian LWBP (kwh)',
    'pdf_wbp'          => 'Pemakaian WBP (kwh)',
    'pdf_disclaimer'   => 'Portal Web Tenant IFCA dapat memuat informasi yang dibuat dan dikelola oleh berbagai sumber, baik internal maupun eksternal. IFCA Building Management tidak bertanggung jawab, baik secara langsung maupun tidak langsung, atas kerusakan atau kerugian apa pun yang timbul atau diduga timbul akibat penggunaan atau ketergantungan pada konten tersebut di Portal Web Tenant IFCA',
];
