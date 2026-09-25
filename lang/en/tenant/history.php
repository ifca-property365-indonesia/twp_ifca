<?php

// Halaman riwayat tenant: Billing, Invoice, Overtime, Ticket History.
return [
    'billing_title'   => 'Billing History',
    'invoice_title'   => 'Invoice History',
    'overtime_title'  => 'Overtime History',
    'ticket_title'    => 'Ticket History',

    // filter
    'start_date_doc'  => 'Start Date (Doc Date)',
    'end_date_doc'    => 'End Date (Doc Date)',

    // kolom tabel
    'col_no'          => 'No.',
    'document_number' => 'Document Number',
    'doc_date'        => 'Doc Date',
    'due_date'        => 'Due Date',
    'description'     => 'Description',
    'period'          => 'Period',
    'currency'        => 'Currency',
    'amount'          => 'Amount',
    'paid'            => 'Paid',
    'paid_date'       => 'Paid Date',
    'outstanding'     => 'Outstanding',
    'id'              => 'ID',
    'request_date'    => 'Request Date',
    'unit'            => 'Unit',
    'start_overtime'  => 'Start Overtime',
    'end_overtime'    => 'End Overtime',
    'ticket_number'   => 'Ticket Number',
    'category'        => 'Category',
    'reported_date'   => 'Reported Date',
    'request_by'      => 'Request By',
    'lot_no'          => 'Lot No',
    'ticket_status'   => 'Ticket Status',

    // status overtime (ot_trx.status)
    'overtime_statuses' => [
        'N'      => 'Waiting for approval',
        'A'      => 'Approved',
        'X'      => 'Canceled',
        'closed' => 'Billed',
    ],

    // status ticket di luar common.statuses (kode dari sistem IFCA)
    'ticket_status_open' => 'Open',
    'wo_number'          => 'WO Number',

    // pesan
    'search_error'    => ':status Search : :error',
];
