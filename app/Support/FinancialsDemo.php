<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Data contoh (hardcode) menu Financials: Overview, Profit & Loss, Balance Sheet, Cash Flow.
 * Seri bulanan Consolidated disalin dari demo FinSight; angka per perusahaan, periode, mata uang
 * dan pembanding dihitung dari seri itu mengikuti filter di atas halaman (group, period, currency, compare).
 * Ganti method di class ini dengan query ke IFCA kalau data aslinya sudah siap.
 *
 * Semua nilai disimpan dalam IDR; konversi mata uang hanya saat ditampilkan (money() / FIN.money di JS).
 */
class FinancialsDemo
{
    /** Bulan terakhir yang ada datanya. */
    public const LATEST = '2026-08';

    /**
     * Seri bulanan Consolidated (Sep 2024 - Agu 2026), urutan kolom sesuai KEYS.
     * Sep - Des 2024 kosong (sama dengan sumber).
     */
    private const MONTHS = [
        '2024-09' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        '2024-10' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        '2024-11' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        '2024-12' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        '2025-01' => [33804251455, 34847100000, 12057854447, 4659970929, 5114217870, 2660072803, 97266596301, 3898409887, -1517446018, -814367568, 1566596301],
        '2025-02' => [32821046521, 34064141400, 11503568673, 4176873352, 5000487769, 2305715331, 98132251344, 3352469395, -1564465773, -922348579, 865655043],
        '2025-03' => [37950907762, 38997405837, 13324004646, 4725341305, 5726019733, 2587186018, 99454100947, 3776294803, -1677749035, -776696165, 1321849603],
        '2025-04' => [37512727349, 38234966289, 13344820777, 5025151336, 5615389605, 2836229743, 100624685090, 3931344894, -1811101573, -949659178, 1170584143],
        '2025-05' => [39730469899, 40965111895, 13926750925, 5189515298, 6017765322, 2900719670, 102386819638, 4411455143, -1588784857, -1060535738, 1762134548],
        '2025-06' => [42632779336, 42565455644, 15350621755, 5806760539, 6254320996, 3295906791, 104544045746, 5104872040, -1823059994, -1124585938, 2157226108],
        '2025-07' => [38578169595, 38222826666, 13915681424, 5466670302, 5617554813, 3152003887, 106156592689, 4536318919, -2025023077, -898748899, 1612546943],
        '2025-08' => [42191240618, 41430118706, 14904691344, 5797439737, 6090349149, 3304657595, 108212506084, 4714065832, -1463132588, -1195019849, 2055913395],
        '2025-09' => [43817977083, 43074629192, 15711197638, 6135280723, 6333574939, 3518528742, 110265505353, 5192252473, -2207852054, -931401150, 2052999269],
        '2025-10' => [44664992815, 44749506442, 16165225628, 6210505101, 6581377855, 3555037823, 112358365210, 5191140376, -2200890771, -897389748, 2092859857],
        '2025-11' => [45383176711, 46455290456, 16377115073, 6442064581, 6833840248, 3718126326, 114387169049, 5249337459, -1990624724, -1229908896, 2028803839],
        '2025-12' => [49022489593, 49449727317, 17584234003, 6969984203, 7276029756, 4022658258, 117272864415, 6020700945, -2071554634, -1063450945, 2885695366],
        '2026-01' => [38703223895, 39376662707, 13797452249, 5376923039, 5795224301, 3081218334, 118848094363, 4333012824, -1668021414, -1089761462, 1575229948],
        '2026-02' => [38164649933, 38501872481, 13644335480, 5266025182, 5667791036, 3010307786, 120428520481, 4388155334, -1896850612, -910878604, 1580426118],
        '2026-03' => [42447760093, 44089199618, 15232837084, 5922181288, 6491793299, 3395642356, 122637966663, 4985357155, -1741082255, -1034828718, 2209446182],
        '2026-04' => [41504661269, 43238363671, 14905115308, 5624056122, 6367985459, 3190183810, 124256492693, 4647488755, -1948707294, -1080255431, 1618526030],
        '2026-05' => [46471044527, 46337721200, 16784199714, 6580297416, 6826022018, 3789015068, 126485364549, 5646145189, -2150721557, -1266551776, 2228871856],
        '2026-06' => [46995700199, 48160362380, 16396117823, 5673957116, 7096150416, 3046293941, 118331185848, 5816352903, -2157770917, -1454429665, 2204152321],
        '2026-07' => [42071068191, 43258069576, 14683240351, 5056855396, 6375292420, 2717904736, 120194934878, 4932700229, -1994715627, -1037585240, 1900399362],
        '2026-08' => [45920469763, 46899944111, 16030140092, 5549303778, 6913612950, 2990838133, 122738191962, 5323414595, -1514493046, -1129810449, 2679111100],
    ];

    private const KEYS = ['revenue', 'budgetRevenue', 'grossProfit', 'ebitda', 'budgetEbitda', 'netProfit',
        'cash', 'operating', 'investing', 'financing', 'netCash'];

    /** Piutang & persediaan Consolidated (Sep 2025 - Agu 2026); bulan lain dihitung dari rasio. */
    private const WORKING_CAPITAL = [
        '2025-09' => [79673114760, 51333189901],
        '2025-10' => [81143641272, 52008980860],
        '2025-11' => [82058108508, 53076451736],
        '2025-12' => [88935559584, 57525986025],
        '2026-01' => [69883430400, 45582245290],
        '2026-02' => [68938166028, 44844091541],
        '2026-03' => [76917151872, 49581607792],
        '2026-04' => [75098754912, 48334379477],
        '2026-05' => [84465282852, 54018940222],
        '2026-06' => [92146791933, 55768837377],
        '2026-07' => [81511914102, 50090723847],
        '2026-08' => [88794933220, 54692870088],
    ];

    /** Rasio terhadap pendapatan / HPP (dari angka Agustus 2026). */
    private const DA_RATE = 0.0275;          // penyusutan & amortisasi / pendapatan
    private const INTEREST_RATE = 0.00984;   // bunga / pendapatan
    private const AR_RATE = 1.934;           // piutang / pendapatan bulanan
    private const INVENTORY_RATE = 1.83;     // persediaan / HPP bulanan
    private const AP_RATE = 1.4787;          // utang usaha / HPP bulanan

    /** Pos neraca tetap per Agustus 2026 + perubahan per bulan (aset tetap naik 0,4%, utang jangka panjang turun 0,3%). */
    private const FIXED_ASSETS = 496.0e9;
    private const OTHER_ASSETS = 50.8e9;
    private const SHORT_DEBT = 61.2e9;
    private const LONG_DEBT = 262.2e9;
    private const OTHER_LIABILITIES = 38.9e9;
    private const CASH_THRESHOLD = 65.0e9;

    /**
     * Perusahaan dalam grup: [porsi pendapatan, pertumbuhan pendapatan per tahun, porsi EBITDA, porsi aset, porsi kas,
     * pencapaian budget pendapatan] per Agustus 2026. Tiap porsi dijumlah = 1, jadi Consolidated = total ke-4 perusahaan.
     */
    private const COMPANIES = [
        'company-a' => [0.438, 0.06, 0.540, 0.414, 0.482, 1.082],   // Mall & Retail
        'company-b' => [0.237, -0.15, 0.104, 0.295, 0.221, 0.826],  // Office
        'company-c' => [0.192, 0.03, 0.179, 0.144, 0.165, 1.000],   // Apartment
        'company-d' => [0.133, 0.17, 0.177, 0.147, 0.132, 0.968],   // Property Development
    ];

    /** KPI operasional tiap bisnis (data operasional contoh): [jenis KPI, nilai %, status]. */
    private const OPERATING_KPIS = [
        'company-a' => ['occupancy', 92.2, 'watch'],
        'company-b' => ['occupancy', 87.3, 'at_risk'],
        'company-c' => ['units_sold', 76.7, 'watch'],
        'company-d' => ['sales_achievement', 75.0, 'watch'],
    ];

    /** Umur piutang & utang usaha (% dari saldo): current, 1-30, 31-60, >60 hari. */
    private const AR_AGING = [41.7, 27.6, 16.3, 14.3];
    private const AP_AGING = [55.4, 25.0, 12.8, 6.8];

    /** Porsi beban (HPP + opex) per kategori; penyusutan dihitung terpisah. */
    private const EXPENSE_MIX = [
        'property_operating' => 34.3,
        'personnel'          => 17.9,
        'utilities'          => 16.6,
        'maintenance'        => 11.3,
        'marketing'          => 9.9,
        'others'             => 7.1,
    ];

    /** Catatan manajemen (teks di lang admin/financials.alerts): [kunci, perusahaan, tingkat, tanggal, ada tindakan?]. */
    private const ALERTS = [
        ['ebitda_below_budget', 'company-b', 'critical', '2026-09-02', true],
        ['cash_below_threshold', 'company-b', 'critical', '2026-09-02', true],
        ['ar_up', 'company-b', 'warning', '2026-09-03', true],
        ['marketing_over_budget', 'company-c', 'warning', '2026-09-01', true],
        ['unsold_units', 'company-c', 'warning', '2026-08-29', false],
    ];

    /** Laporan terbaru: [kunci, tab tujuan (null = belum ada halaman), waktu update, format]. */
    private const REPORTS = [
        ['pl', 'profit-loss', '2026-09-26 08:15', 'Excel / PDF'],
        ['bs', 'balance-sheet', '2026-09-26 08:15', 'Excel / PDF'],
        ['cf', 'cash-flow', '2026-09-25 17:40', 'Excel / PDF'],
        ['ar_aging', null, '2026-09-25 11:02', 'Excel'],
        ['ap_aging', null, '2026-09-25 11:02', 'Excel'],
        ['occupancy', null, '2026-09-24 09:30', 'PDF'],
    ];

    /** Mata uang tampilan: [kurs ke IDR, simbol]. */
    private const CURRENCIES = [
        'IDR' => [1, 'Rp'],
        'USD' => [16250, '$'],
        'SGD' => [12650, 'S$'],
        'MYR' => [3650, 'RM'],
    ];

    private const COMPARES = ['py', 'pp', 'budget'];

    /** Mata uang aktif untuk money() (diset oleh constructor). */
    private static string $currency = 'IDR';

    /** Filter aktif: group, period, currency, compare. */
    public array $filter;

    /** Bulan-bulan dalam periode (urut) dan bulan terakhirnya. */
    private array $months;
    private string $end;

    public function __construct(array $filter)
    {
        $this->filter = self::normalize($filter);
        $this->months = self::periods()[$this->filter['period']];
        $this->end = end($this->months);
        self::$currency = $this->filter['currency'];
    }

    // ------------------------------------------------------------------
    // Filter
    // ------------------------------------------------------------------

    /** Filter default = Consolidated, bulan terakhir, IDR, vs tahun lalu. */
    public static function normalize(array $in): array
    {
        $pick = fn ($value, array $allowed, $default) => in_array($value, $allowed, true) ? $value : $default;
        return [
            'group'    => $pick($in['group'] ?? null, array_merge(['consolidated'], array_keys(self::COMPANIES)), 'consolidated'),
            'period'   => $pick($in['period'] ?? null, array_keys(self::periods()), self::LATEST),
            'currency' => $pick($in['currency'] ?? null, array_keys(self::CURRENCIES), 'IDR'),
            'compare'  => $pick($in['compare'] ?? null, self::COMPARES, 'py'),
        ];
    }

    /** Periode yang bisa dipilih => daftar bulannya (Jan - Agu 2026, kuartal, YTD, FY 2025). */
    public static function periods(): array
    {
        $range = fn ($from, $to) => array_map(fn ($m) => sprintf('%s-%02d', substr($from, 0, 4), $m), range((int) substr($from, 5), (int) substr($to, 5)));
        $periods = [];
        for ($m = (int) substr(self::LATEST, 5); $m >= 1; $m--) {
            $key = substr(self::LATEST, 0, 5) . sprintf('%02d', $m);
            $periods[$key] = [$key];
        }
        return $periods + [
            '2026-q3'  => $range('2026-07', self::LATEST),
            '2026-q2'  => $range('2026-04', '2026-06'),
            '2026-q1'  => $range('2026-01', '2026-03'),
            '2026-ytd' => $range('2026-01', self::LATEST),
            '2025-fy'  => $range('2025-01', '2025-12'),
        ];
    }

    /** Pilihan tiap dropdown filter: [kunci => label]. */
    public static function options(): array
    {
        $groups = ['consolidated' => __('admin/financials.consolidated')];
        foreach (array_keys(self::COMPANIES) as $key) {
            $groups[$key] = __('admin/financials.groups.' . $key);
        }
        $periods = [];
        foreach (array_keys(self::periods()) as $key) {
            $periods[$key] = self::periodLabel($key);
        }
        $currencies = [];
        foreach (array_keys(self::CURRENCIES) as $code) {
            $currencies[$code] = $code . ' — ' . __('admin/financials.currencies.' . $code);
        }
        $compares = [];
        foreach (self::COMPARES as $key) {
            $compares[$key] = __('admin/financials.compares.' . $key);
        }
        return ['group' => $groups, 'period' => $periods, 'currency' => $currencies, 'compare' => $compares];
    }

    /** Label periode, mis. "August 2026", "Q3 2026 (to date)", "YTD 2026 (Jan–Aug)", "FY 2025". */
    public static function periodLabel(string $key): string
    {
        $months = self::periods()[$key];
        $first = Carbon::parse($months[0] . '-01')->locale(app()->getLocale());
        $last = Carbon::parse(end($months) . '-01')->locale(app()->getLocale());
        $year = substr($key, 0, 4);
        return match (true) {
            count($months) === 1        => $first->translatedFormat('F Y'),
            str_ends_with($key, '-ytd') => __('admin/financials.period_ytd', ['year' => $year, 'range' => $first->translatedFormat('M') . '–' . $last->translatedFormat('M')]),
            str_ends_with($key, '-fy')  => __('admin/financials.period_fy', ['year' => $year]),
            // kuartal berjalan (belum 3 bulan) diberi keterangan "to date"
            default => __(count($months) < 3 ? 'admin/financials.period_q_to_date' : 'admin/financials.period_q', ['q' => substr($key, -1), 'year' => $year]),
        };
    }

    /** Teks untuk judul & keterangan halaman. */
    public function labels(): array
    {
        $options = self::options();
        return [
            'group'    => $options['group'][$this->filter['group']],
            'period'   => self::periodLabel($this->filter['period']),
            'currency' => $this->filter['currency'],
            'compare'  => mb_strtolower($options['compare'][$this->filter['compare']]),
        ];
    }

    /** Konfigurasi mata uang untuk JS: kurs & simbol. */
    public function currency(): array
    {
        [$rate, $symbol] = self::CURRENCIES[$this->filter['currency']];
        return ['code' => $this->filter['currency'], 'rate' => $rate, 'symbol' => $symbol];
    }

    // ------------------------------------------------------------------
    // Halaman
    // ------------------------------------------------------------------

    /** Seri bulanan untuk grafik: $count bulan sampai akhir periode (12 = rolling 12 bulan, 24 = mode Yearly). */
    public function monthly(int $count = 12): array
    {
        $rows = [];
        foreach ($this->window($count) as $month) {
            $r = $this->row($month);
            $rows[] = [
                'month'         => $month,
                'label'         => self::label($month),
                'revenue'       => $r['revenue'],
                'budgetRevenue' => $r['budgetRevenue'],
                'priorRevenue'  => $this->row(self::shift($month, -12))['revenue'],
                'grossMargin'   => $r['revenue'] ? round($r['grossProfit'] / $r['revenue'] * 100, 4) : null,
                'ebitda'        => $r['ebitda'],
                'budgetEbitda'  => $r['budgetEbitda'],
                'netProfit'     => $r['netProfit'],
                'cash'          => $r['cash'],
                'netCash'       => $r['netCash'],
            ];
        }
        return $rows;
    }

    /** Rasio P&L (nilai %, perubahan dalam poin vs pembanding). */
    public function plKpis(): array
    {
        $cur = $this->statement($this->months);
        $cmp = $this->compareStatement();
        $kpis = [];
        foreach (['gross_margin' => 'gross_profit', 'ebitda_margin' => 'ebitda', 'net_margin' => 'net_profit', 'opex_ratio' => 'opex'] as $key => $line) {
            $value = self::ratio($cur[$line], $cur['revenue']);
            $kpis[$key] = ['value' => $value, 'pts' => $cmp['revenue'] ? $value - self::ratio($cmp[$line], $cmp['revenue']) : 0];
        }
        return $kpis;
    }

    /** Laporan laba rugi: [pos, aktual, budget, % vs budget, tahun lalu, % YoY, baris total?]. */
    public function plStatement(): array
    {
        $actual = $this->statement($this->months);
        $budget = $this->statement($this->months, true);
        $prior = $this->statement(array_map(fn ($m) => self::shift($m, -12), $this->months));
        $totals = ['revenue', 'gross_profit', 'ebitda', 'ebit', 'net_profit'];
        $rows = [];
        foreach ($actual as $line => $value) {
            $rows[] = [$line, $value, $budget[$line], self::change($value, $budget[$line]), $prior[$line], self::change($value, $prior[$line]), in_array($line, $totals, true)];
        }
        return $rows;
    }

    /** Neraca per akhir periode. */
    public function balanceSheet(): array
    {
        $bs = $this->position($this->end);
        $prev = $this->position(self::shift($this->end, -1));
        $mom = fn ($key) => self::change($bs[$key], $prev[$key]);
        $currentAssets = $bs['cash'] + $bs['receivables'] + $bs['inventory'];
        $currentLiabilities = $bs['payables'] + $bs['short_debt'];

        $trend = [];
        foreach ($this->window(12) as $month) {
            $p = $this->position($month);
            $trend[] = [$month, $p['cash'], $p['receivables'], $p['inventory']];
        }

        return [
            'kpis' => [
                'current_ratio'   => ['value' => $currentLiabilities ? $currentAssets / $currentLiabilities : 0, 'target' => 1.50],
                'debt_equity'     => ['value' => $bs['equity'] ? $bs['liabilities_total'] / $bs['equity'] : 0, 'covenant' => 2.00],
                'working_capital' => $currentAssets - $currentLiabilities,
                'ar_days'         => ['value' => (int) round($bs['ar_days']), 'mom' => self::change($bs['ar_days'], $prev['ar_days'])],
                'ap_days'         => (int) round($bs['ap_days']),
                'inventory_days'  => (int) round($bs['inventory_days']),
            ],
            // [pos, nilai, % MoM]
            'assets' => array_map(fn ($k) => [$k, $bs[$k], $mom($k)], ['cash', 'receivables', 'inventory', 'fixed_assets', 'other_assets']),
            'assets_total' => $bs['assets_total'],
            'liabilities' => array_map(fn ($k) => [$k, $bs[$k], $mom($k)], ['payables', 'short_debt', 'long_debt', 'other_liabilities']),
            'liabilities_total' => $bs['liabilities_total'],
            'equity' => $bs['equity'],
            'equity_pct' => self::ratio($bs['equity'], $bs['assets_total']),
            // modal kerja 12 bulan: [bulan, kas, piutang, persediaan]
            'working_capital_trend' => $trend,
        ];
    }

    /** Arus kas periode. */
    public function cashFlow(): array
    {
        $ending = $this->row($this->end)['cash'];
        $net = $this->sum($this->months, 'netCash');

        // arus masuk = kas operasi; arus keluar = kas operasi - arus kas bersih
        $inOut = [];
        foreach ($this->window(12) as $month) {
            $r = $this->row($month);
            $inOut[] = [$month, $r['operating'], $r['operating'] - $r['netCash']];
        }

        // proyeksi 4 bulan dari rata-rata arus kas bersih 6 bulan terakhir
        $last6 = $this->window(6);
        $step = $this->sum($last6, 'netCash') / 6;
        $forecast = [];
        foreach ($last6 as $month) {
            $cash = $this->row($month)['cash'];
            $forecast[] = [$month, $cash, $month === $this->end ? $cash : null];
        }
        for ($i = 1; $i <= 4; $i++) {
            $forecast[] = [self::shift($this->end, $i), null, $ending + $step * $i];
        }

        return [
            'beginning'  => $ending - $net,
            'operating'  => $this->sum($this->months, 'operating'),
            'investing'  => $this->sum($this->months, 'investing'),
            'financing'  => $this->sum($this->months, 'financing'),
            'net'        => $net,
            'ending'     => $ending,
            'ending_mom' => self::change($ending, $this->row(self::shift($this->end, -1))['cash']),
            'threshold'  => self::CASH_THRESHOLD * $this->shares($this->end)['cash'],
            // [bulan, inflow, outflow]
            'inflow_outflow' => $inOut,
            // [bulan, kas aktual, forecast]
            'forecast' => $forecast,
        ];
    }

    /**
     * Semua data halaman Overview (tampilan dashboard eksekutif): kartu KPI + sparkline, tren pendapatan / EBITDA,
     * ringkasan laba rugi, catatan manajemen, ringkasan kas & neraca, umur piutang / utang, kinerja per bisnis,
     * pendapatan per unit, beban per kategori, dan laporan terbaru.
     */
    public function dashboard(): array
    {
        $cur = $this->statement($this->months);
        $budget = $this->statement($this->months, true);
        $cmp = $this->compareStatement();
        $bs = $this->position($this->end);
        $net = $this->sum($this->months, 'netCash');

        // sparkline 12 bulan per kartu
        $spark = ['revenue' => [], 'gross_profit' => [], 'ebitda' => [], 'net_profit' => [], 'cash' => [], 'assets' => []];
        foreach ($this->window(12) as $month) {
            $m = $this->statement([$month]);
            $p = $this->position($month);
            foreach (['revenue', 'gross_profit', 'ebitda', 'net_profit'] as $k) {
                $spark[$k][] = $m[$k];
            }
            $spark['cash'][] = $p['cash'];
            $spark['assets'][] = $p['assets_total'];
        }

        $kpis = [];
        foreach (['revenue', 'gross_profit', 'ebitda', 'net_profit'] as $k) {
            $kpis[$k] = [
                'value'  => $cur[$k],
                'budget' => $budget[$k],
                'change' => self::change($cur[$k], $cmp[$k]),
                'margin' => $k === 'revenue' ? null : self::ratio($cur[$k], $cur['revenue']),
                'spark'  => $spark[$k],
            ];
        }
        $kpis['closing_cash'] = ['value' => $bs['cash'], 'min' => self::CASH_THRESHOLD * $this->shares($this->end)['cash'], 'spark' => $spark['cash']];
        $kpis['total_assets'] = ['value' => $bs['assets_total'], 'equity' => $bs['equity'], 'spark' => $spark['assets']];

        // per perusahaan (selalu ke-4 bisnis, dengan periode yang sama)
        $companies = [];
        foreach (array_keys(self::COMPANIES) as $key) {
            $c = new self(['group' => $key] + $this->filter);
            $cs = $c->statement($c->months);
            $cb = $c->statement($c->months, true);
            $cp = $c->statement(array_map(fn ($m) => self::shift($m, -12), $c->months));
            $achievement = self::ratio($cs['revenue'], $cb['revenue']);
            [$kpiType, $kpiValue, $kpiStatus] = self::OPERATING_KPIS[$key];
            $companies[$key] = [
                'revenue'    => $cs['revenue'],
                'ebitda'     => $cs['ebitda'],
                'margin'     => self::ratio($cs['ebitda'], $cs['revenue']),
                'net_profit' => $cs['net_profit'],
                'cash'       => $c->position($c->end)['cash'],
                'budget_pct' => $achievement,
                'yoy'        => self::change($cs['revenue'], $cp['revenue']),
                'status'     => $achievement >= 105 ? 'on_track' : ($achievement < 90 ? 'at_risk' : 'attention'),
                'kpi'        => ['type' => $kpiType, 'value' => $kpiValue, 'status' => $kpiStatus],
            ];
        }
        $totalRevenue = array_sum(array_column($companies, 'revenue'));

        // beban per kategori: HPP + opex dibagi menurut porsi, ditambah penyusutan
        $opCost = $cur['cogs'] + $cur['opex'];
        $mix = array_sum(self::EXPENSE_MIX);
        $expenses = [];
        foreach (self::EXPENSE_MIX as $k => $w) {
            $expenses[$k] = $opCost * $w / $mix;
        }
        $expenses['da'] = $cur['da'];
        $totalExpense = array_sum($expenses);

        $aging = fn (float $total, array $pcts) => [
            'total'   => $total,
            'buckets' => array_map(fn ($p) => ['value' => $total * $p / 100, 'pct' => $p], array_combine(['current', 'd30', 'd60', 'over60'], $pcts)),
        ];

        // catatan manajemen: semua untuk Consolidated, atau milik perusahaan terpilih saja
        $group = $this->filter['group'];
        $alerts = array_values(array_filter(
            array_map(fn ($a) => array_combine(['key', 'company', 'severity', 'date', 'action'], $a), self::ALERTS),
            fn ($a) => $group === 'consolidated' || $a['company'] === $group
        ));

        return [
            'kpis'      => $kpis,
            'trend'     => $this->trend(),
            'statement' => $this->plStatement(),
            'alerts'    => $alerts,
            'cash'      => ['in' => $cur['revenue'], 'out' => $cur['revenue'] - $net, 'net' => $net, 'closing' => $bs['cash']],
            'bs'        => [
                'assets'      => $bs['assets_total'],
                'liabilities' => $bs['liabilities_total'],
                'equity'      => $bs['equity'],
                'de'          => $bs['equity'] ? $bs['liabilities_total'] / $bs['equity'] : 0,
                'liab_pct'    => self::ratio($bs['liabilities_total'], $bs['assets_total']),
                'equity_pct'  => self::ratio($bs['equity'], $bs['assets_total']),
            ],
            'ar'        => $aging($bs['receivables'], self::AR_AGING),
            'ap'        => $aging($bs['payables'], self::AP_AGING),
            'companies' => $companies,
            'units'     => array_map(fn ($c) => ['value' => $c['revenue'], 'pct' => self::ratio($c['revenue'], $totalRevenue)], $companies),
            'expenses'  => array_map(fn ($v) => ['value' => $v, 'pct' => self::ratio($v, $totalExpense)], $expenses),
            'reports'   => array_map(fn ($r) => array_combine(['key', 'tab', 'updated', 'format'], $r), self::REPORTS),
        ];
    }

    /**
     * Grafik tren (pendapatan & EBITDA: [aktual, budget, tahun lalu]) dalam 3 tampilan:
     * rolling 12 bulan, bulanan Jan - Des tahun periode, dan YTD kumulatif.
     */
    private function trend(): array
    {
        $point = function (string $month) {
            $r = $this->row($month);
            $p = $this->row(self::shift($month, -12));
            $has = isset(self::MONTHS[$month]);
            return [
                'revenue' => [$has ? $r['revenue'] : null, $has ? $r['budgetRevenue'] : null, $p['revenue'] ?: null],
                'ebitda'  => [$has ? $r['ebitda'] : null, $has ? $r['budgetEbitda'] : null, $p['ebitda'] ?: null],
            ];
        };
        $series = function (array $months, bool $cumulative = false) use ($point) {
            $out = ['labels' => array_map(fn ($m) => self::label($m), $months), 'revenue' => [[], [], []], 'ebitda' => [[], [], []]];
            $run = ['revenue' => [0, 0, 0], 'ebitda' => [0, 0, 0]];
            foreach ($months as $month) {
                foreach ($point($month) as $metric => $values) {
                    foreach ($values as $i => $v) {
                        if ($cumulative && $v !== null) {
                            $run[$metric][$i] += $v;
                            $v = $run[$metric][$i];
                        }
                        $out[$metric][$i][] = $v;
                    }
                }
            }
            return $out;
        };
        $year = substr($this->end, 0, 4);
        $month = (int) substr($this->end, 5);
        return [
            'rolling' => $series($this->window(12)),
            'monthly' => $series(array_map(fn ($m) => sprintf('%s-%02d', $year, $m), range(1, 12))),
            'ytd'     => $series(array_map(fn ($m) => sprintf('%s-%02d', $year, $m), range(1, $month)), true),
            'year'    => $year,
        ];
    }

    // ------------------------------------------------------------------
    // Perhitungan
    // ------------------------------------------------------------------

    /**
     * Porsi group terpilih pada bulan tertentu. Porsi pendapatan & EBITDA bergeser mengikuti
     * pertumbuhan tiap perusahaan (dinormalkan supaya totalnya tetap 1); porsi aset & kas tetap.
     */
    private function shares(string $month): array
    {
        $group = $this->filter['group'];
        if (!isset(self::COMPANIES[$group])) {
            return ['revenue' => 1, 'ebitda' => 1, 'budget_revenue' => 1, 'budget_ebitda' => 1, 'assets' => 1, 'cash' => 1];
        }
        $years = self::monthsFrom(self::LATEST, $month) / 12;
        $rev = $ebitda = $budRev = $budEbitda = [];
        foreach (self::COMPANIES as $key => [$revShare, $growth, $ebitdaShare, , , $achievement]) {
            $factor = (1 + $growth) ** $years;
            $rev[$key] = $revShare * $factor;
            $ebitda[$key] = $ebitdaShare * $factor;
            // porsi budget: perusahaan yang melampaui budget punya porsi budget lebih kecil
            $budRev[$key] = $rev[$key] / $achievement;
            $budEbitda[$key] = $ebitda[$key] / $achievement;
        }
        return [
            'revenue' => $rev[$group] / array_sum($rev),
            'ebitda'  => $ebitda[$group] / array_sum($ebitda),
            'budget_revenue' => $budRev[$group] / array_sum($budRev),
            'budget_ebitda'  => $budEbitda[$group] / array_sum($budEbitda),
            'assets'  => self::COMPANIES[$group][3],
            'cash'    => self::COMPANIES[$group][4],
        ];
    }

    /** Satu bulan untuk group terpilih (IDR). Bulan tanpa data = 0. */
    private function row(string $month): array
    {
        $raw = array_combine(self::KEYS, self::MONTHS[$month] ?? array_fill(0, count(self::KEYS), 0));
        $s = $this->shares($month);
        return [
            'revenue'       => $raw['revenue'] * $s['revenue'],
            'budgetRevenue' => $raw['budgetRevenue'] * $s['budget_revenue'],
            'grossProfit'   => $raw['grossProfit'] * $s['revenue'],
            'ebitda'        => $raw['ebitda'] * $s['ebitda'],
            'budgetEbitda'  => $raw['budgetEbitda'] * $s['budget_ebitda'],
            // laba bersih perusahaan dihitung ulang di statement() dari tarif pajak Consolidated
            'netProfit'     => $raw['netProfit'] * $s['ebitda'],
            'taxRate'       => self::taxRate($raw),
            'cash'          => $raw['cash'] * $s['cash'],
            'operating'     => $raw['operating'] * $s['cash'],
            'investing'     => $raw['investing'] * $s['cash'],
            'financing'     => $raw['financing'] * $s['cash'],
            'netCash'       => $raw['netCash'] * $s['cash'],
        ];
    }

    /** Tarif pajak efektif Consolidated: (laba sebelum pajak - laba bersih) / laba sebelum pajak. */
    private static function taxRate(array $raw): float
    {
        $ebt = $raw['ebitda'] - $raw['revenue'] * (self::DA_RATE + self::INTEREST_RATE);
        return $ebt > 0 ? ($ebt - $raw['netProfit']) / $ebt : 0;
    }

    /** Laba rugi untuk sekumpulan bulan (aktual atau budget). */
    private function statement(array $months, bool $budget = false): array
    {
        $t = array_fill_keys(['revenue', 'cogs', 'gross_profit', 'opex', 'ebitda', 'da', 'ebit', 'interest', 'tax', 'net_profit'], 0);
        foreach ($months as $month) {
            $r = $this->row($month);
            // budget: HPP & pos lain mengikuti rasio pendapatan budget / aktual
            $scale = $budget ? ($r['revenue'] ? $r['budgetRevenue'] / $r['revenue'] : 0) : 1;
            $revenue = $r['revenue'] * $scale;
            $cogs = ($r['revenue'] - $r['grossProfit']) * $scale;
            $ebitda = $budget ? $r['budgetEbitda'] : $r['ebitda'];
            $da = $revenue * self::DA_RATE;
            $interest = $revenue * self::INTEREST_RATE;
            $ebt = $ebitda - $da - $interest;
            $tax = $ebt * $r['taxRate'];

            $t['revenue'] += $revenue;
            $t['cogs'] += $cogs;
            $t['gross_profit'] += $revenue - $cogs;
            $t['opex'] += $revenue - $cogs - $ebitda;
            $t['ebitda'] += $ebitda;
            $t['da'] += $da;
            $t['ebit'] += $ebitda - $da;
            $t['interest'] += $interest;
            $t['tax'] += $tax;
            $t['net_profit'] += $ebt - $tax;
        }
        return $t;
    }

    /** Laba rugi pembanding: tahun lalu, periode sebelumnya (panjang sama), atau budget. */
    private function compareStatement(): array
    {
        return match ($this->filter['compare']) {
            'budget' => $this->statement($this->months, true),
            'pp'     => $this->statement(array_map(fn ($m) => self::shift($m, -count($this->months)), $this->months)),
            default  => $this->statement(array_map(fn ($m) => self::shift($m, -12), $this->months)),
        };
    }

    /** Posisi neraca akhir bulan (IDR), termasuk hari piutang / utang / persediaan. */
    private function position(string $month): array
    {
        $raw = array_combine(self::KEYS, self::MONTHS[$month] ?? array_fill(0, count(self::KEYS), 0));
        $s = $this->shares($month);
        $n = self::monthsFrom(self::LATEST, $month);
        $cogs = $raw['revenue'] - $raw['grossProfit'];
        [$ar, $inventory] = self::WORKING_CAPITAL[$month] ?? [$raw['revenue'] * self::AR_RATE, $cogs * self::INVENTORY_RATE];

        $p = [
            'cash'              => $raw['cash'] * $s['cash'],
            'receivables'       => $ar * $s['revenue'],
            'inventory'         => $inventory * $s['revenue'],
            'fixed_assets'      => self::FIXED_ASSETS * (1 + 0.004 * $n) * $s['assets'],
            'other_assets'      => self::OTHER_ASSETS * $s['assets'],
            'payables'          => $cogs * self::AP_RATE * $s['revenue'],
            'short_debt'        => self::SHORT_DEBT * $s['assets'],
            'long_debt'         => self::LONG_DEBT * (1 - 0.003 * $n) * $s['assets'],
            'other_liabilities' => self::OTHER_LIABILITIES * $s['assets'],
        ];
        $p['assets_total'] = $p['cash'] + $p['receivables'] + $p['inventory'] + $p['fixed_assets'] + $p['other_assets'];
        $p['liabilities_total'] = $p['payables'] + $p['short_debt'] + $p['long_debt'] + $p['other_liabilities'];
        $p['equity'] = $p['assets_total'] - $p['liabilities_total'];

        // hari = saldo / nilai bulanan x 30,4
        $monthRevenue = $raw['revenue'] * $s['revenue'];
        $monthCogs = $cogs * $s['revenue'];
        $p['ar_days'] = $monthRevenue ? $p['receivables'] / $monthRevenue * 30.4 : 0;
        $p['ap_days'] = $monthCogs ? $p['payables'] / $monthCogs * 30.4 : 0;
        $p['inventory_days'] = $monthCogs ? $p['inventory'] / $monthCogs * 30.4 : 0;
        return $p;
    }

    /** Jumlah satu kolom untuk sekumpulan bulan. */
    private function sum(array $months, string $key): float
    {
        return array_sum(array_map(fn ($m) => $this->row($m)[$key], $months));
    }

    /** $count bulan berturut-turut sampai akhir periode. */
    private function window(int $count): array
    {
        return array_map(fn ($i) => self::shift($this->end, $i), range(1 - $count, 0));
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** Geser bulan, mis. shift('2026-08', -12) -> '2025-08'. */
    private static function shift(string $month, int $by): string
    {
        return Carbon::parse($month . '-01')->addMonthsNoOverflow($by)->format('Y-m');
    }

    /** Selisih bulan $month terhadap $from (negatif = sebelum). */
    private static function monthsFrom(string $from, string $month): int
    {
        [$fy, $fm] = array_map('intval', explode('-', $from));
        [$y, $m] = array_map('intval', explode('-', $month));
        return ($y - $fy) * 12 + ($m - $fm);
    }

    /** Perubahan % terhadap pembanding; 0 kalau pembanding kosong. */
    private static function change($value, $base): float
    {
        return $base ? ($value - $base) / abs($base) * 100 : 0;
    }

    /** Persentase $part terhadap $whole. */
    private static function ratio($part, $whole): float
    {
        return $whole ? $part / $whole * 100 : 0;
    }

    /** Label bulan singkat, mis. '2026-08' -> 'Aug 26'. */
    public static function label(string $month): string
    {
        return date('M y', strtotime($month . '-01'));
    }

    /**
     * Nilai uang ringkas dalam mata uang aktif. IDR seperti sumber: Rp 45,9 M (miliar), Rp 451,9 jt (juta);
     * mata uang lain: $ 2,8 M (juta), $ 341,5 K (ribu), $ 1,2 B (miliar).
     */
    public static function money($value): string
    {
        [$rate, $symbol] = self::CURRENCIES[self::$currency];
        $v = (float) $value / $rate;
        $abs = abs($v);
        $sign = $v < 0 ? '-' : '';
        $units = self::$currency === 'IDR' ? [[1e9, 'M'], [1e6, 'jt']] : [[1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
        foreach ($units as [$size, $unit]) {
            if ($abs >= $size) {
                return $sign . $symbol . ' ' . number_format($abs / $size, 1, ',', '.') . ' ' . $unit;
            }
        }
        return $sign . $symbol . ' ' . number_format($abs, 0, ',', '.');
    }

    /** Persen dengan tanda, mis. +8,8% / -4,3%. */
    public static function pct($value, int $decimals = 1): string
    {
        $value = round($value, $decimals);
        return ($value > 0 ? '+' : ($value < 0 ? '-' : '')) . number_format(abs($value), $decimals, ',', '.') . '%';
    }
}
