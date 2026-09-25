<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Jam kerja gedung untuk request lembur (overtime).
 *
 * Jam kerja per entity dari mgr.cf_workhour (day_type 'D' = Senin-Jumat, 'E' = Sabtu).
 * Tabel itu masih kosong di jbc_live, jadi dipakai default sesuai disclaimer form lembur:
 * Senin-Jumat 07:00-18:00, Sabtu 07:00-13:00. Minggu & hari libur (mgr.cf_holiday) tidak
 * ada jam kerja: seluruh hari dihitung lembur.
 */
class OvertimeHours
{
    const DEFAULTS = [
        'D' => ['07:00', '18:00'],
        'E' => ['07:00', '13:00'],
    ];

    /** Batas jam pengajuan lembur untuk hari yang sama (lewat jam ini hanya bisa untuk besok dst.). */
    const SAME_DAY_CUTOFF = '16:00';

    /**
     * Jam kerja tanggal $date (Y-m-d) untuk entity ini: ['begin' => 'HH:MM', 'end' => 'HH:MM'],
     * atau null kalau Minggu / hari libur.
     */
    public static function workHours($entity, $date)
    {
        $day = (int) date('N', strtotime($date));   // 1 = Senin ... 7 = Minggu
        if ($day === 7 || self::isHoliday($date)) {
            return null;
        }

        $type = $day === 6 ? 'E' : 'D';
        $row = null;
        try {
            $row = DB::connection('dblive')->table('mgr.cf_workhour')
                ->whereRaw('RTRIM(entity_cd) = ?', [trim((string) $entity)])
                ->whereRaw('RTRIM(day_type) = ?', [$type])
                ->first(['begin_time', 'end_time']);
        } catch (\Throwable $e) {
            $row = null;
        }

        if ($row && $row->begin_time && $row->end_time) {
            return [
                'begin' => date('H:i', strtotime($row->begin_time)),
                'end'   => date('H:i', strtotime($row->end_time)),
            ];
        }

        return ['begin' => self::DEFAULTS[$type][0], 'end' => self::DEFAULTS[$type][1]];
    }

    /** Tanggal libur di mgr.cf_holiday? (tanggal dikirim yyyymmdd, lihat BasePermitController::fmtDate) */
    public static function isHoliday($date)
    {
        try {
            return DB::connection('dblive')->table('mgr.cf_holiday')
                ->whereRaw('CONVERT(date, holiday) = ?', [date('Ymd', strtotime($date))])
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Bagian periode lembur [$start, $end) (datetime 'Y-m-d H:i:s') yang di luar jam kerja,
     * dipecah per hari -> [['start' => ..., 'end' => ...], ...] untuk ot_trxdt.
     */
    public static function segments($entity, $start, $end)
    {
        $segments = [];
        $cursor = strtotime($start);
        $stop = strtotime($end);

        while ($cursor < $stop) {
            $date = date('Y-m-d', $cursor);
            $dayEnd = min($stop, strtotime($date . ' +1 day'));
            $work = self::workHours($entity, $date);

            if ($work === null) {
                $segments[] = [$cursor, $dayEnd];
            } else {
                $wBegin = strtotime($date . ' ' . $work['begin']);
                $wEnd = strtotime($date . ' ' . $work['end']);
                if ($cursor < $wBegin) {
                    $segments[] = [$cursor, min($dayEnd, $wBegin)];
                }
                if ($dayEnd > $wEnd) {
                    $segments[] = [max($cursor, $wEnd), $dayEnd];
                }
            }
            $cursor = $dayEnd;
        }

        return array_map(function ($s) {
            return ['start' => date('Y-m-d H:i:s', $s[0]), 'end' => date('Y-m-d H:i:s', $s[1])];
        }, array_filter($segments, function ($s) { return $s[1] > $s[0]; }));
    }
}
