<?php

// Admin: Dashboard (admin/dash) & ekspor PDF pemakaian listrik.
return [
    'title'            => 'Dashboard',
    'heading'          => 'Dashboard Administrator',
    'welcome'          => 'Welcome to Tenant Web Portal.',
    'work_order_graphic' => 'Work Order Graphic',
    'utility_usage'    => 'Utility Usage',
    'water'            => 'Water',
    'gas'              => 'Gas',
    'electric'         => 'Electric',
    'ticket'           => 'Ticket',
    'col_no'           => 'No.',
    'ticket_number'    => 'Ticket Number',
    'wo_number'        => 'WO Number',
    'tenant_name'      => 'Tenant Name',
    'reported_date'    => 'Reported Date',
    'request_by'       => 'Request By',
    'ticket_status'    => 'Ticket Status',
    'lwbp_label'       => 'LWBP (Lewat Waktu Beban Puncak) 22:00 - 17:00',
    'wbp_label'        => 'WBP (Waktu Beban Puncak) 17:00 - 22:00',
    'usage'            => 'Usage',

    // baris grafik status work order (kunci = kode baris, jangan diubah)
    'wo_status' => [
        'Submit'    => 'Submit',
        'Open'      => 'Open',
        'Assigned'  => 'Assigned',
        'Process'   => 'Process',
        'Confirm'   => 'Confirm',
        'Closed'    => 'Closed',
        'Cancelled' => 'Cancelled',
        'Total'     => 'Total',
    ],

    // label status ticket di tabel dashboard (kunci = kode status)
    'ticket_statuses' => [
        'O' => 'Open',
        'R' => 'Submit',
        'A' => 'Assigned',
        'Y' => 'Approve',
        'C' => 'Close',
        'X' => 'Cancel',
        'S' => 'Survey',
        'P' => 'Process',
        'M' => 'Modify',
        'Z' => 'Charged Approved',
        'F' => 'Confirm',
    ],

    'months_short' => [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ],

    // PDF
    'pdf_heading'      => 'Electricity Summary Usage',
    'pdf_lwbp'         => 'LWBP usage (kwh)',
    'pdf_wbp'          => 'WBP usage (kwh)',
    'pdf_disclaimer'   => 'IFCA Tenant Web Portal may contain information that is created and managed by various sources, both internal and external. At no time shall IFCA Building Management be responsible or liable, directly or indirectly, for any damage or loss resulting from or alleged to result from the use of or reliance on any such content in IFCA Tenant Web Portal',
];
