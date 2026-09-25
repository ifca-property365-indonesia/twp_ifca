<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // <-- WAJIB DITAMBAHKAN
use Exception;

class UserSurveyController extends Controller
{
    public function index()
    {
        $today = date('Y-m-d');
        
        // Ambil data user dari session
        $business_no = Session::get('business_no');
        $email = Session::get('Tenemail');

        // 1. Cari tahu ID Survey mana saja yang sudah pernah dijawab oleh user ini
        $answeredSurveyIds = DB::table('mgr.survey_respondents')
            ->where('email', $email)
            ->whereIn('business_no', TenantScope::businessNos())
            ->pluck('survey_id') // Hanya ambil kolom survey_id
            ->toArray(); // Ubah menjadi bentuk Array, contoh: [3]

        // 2. Ambil survey (Published + Tanggal Berlaku + BELUM DIJAWAB)
        $surveys = DB::table('mgr.surveys')
            ->where('status', 'published')
            ->whereDate('publish_date', '<=', $today)
            ->whereDate('expired_date', '>=', $today)
            ->whereNotIn('id', $answeredSurveyIds) // Mencegah ID yang sudah dijawab muncul lagi
            ->orderBy('publish_date', 'desc') // Diurutkan dari yang terbaru
            ->paginate(10); // <-- UBAH ->get() MENJADI ->paginate(10) DI SINI

        // 3. Ambil pertanyaan dan opsi untuk masing-masing survey
        foreach ($surveys as $survey) {
            $survey->questions = DB::table('mgr.survey_questions')
                ->where('survey_id', $survey->id)
                ->orderBy('order_no', 'asc')
                ->get();

            foreach ($survey->questions as $q) {
                if ($q->question_type == 'multiple_choice') {
                    $q->options = DB::table('mgr.survey_question_options')
                        ->where('question_id', $q->id)
                        ->get();
                } else {
                    $q->options = [];
                }
            }
        }

        return view('tenant.survey.user.index', compact('surveys')); 
    }

    public function submit(Request $request)
    {
        // Memulai transaksi database agar aman jika terjadi error di tengah proses
        DB::connection('mysql')->beginTransaction();
        
        try {
            $waktuSekarang = date('Y-m-d H:i:s');
            
            // --- AMBIL DATA DARI SESSION ---
            $business_no = Session::get('business_no');
            $email = Session::get('Tenemail'); 
            
            // 1. Simpan Data Responden
            $respondentId = DB::connection('mysql')->table('mgr.survey_respondents')->insertGetId([
                'survey_id'   => $request->survey_id,
                'email'       => $email, // Mengutamakan email dari session
                'business_no' => $business_no, // <-- Menyimpan business_no dari session
                'created_at'  => $waktuSekarang,
            ]);

            // 2. Proses dan Kumpulkan Data Jawaban
            $answersData = [];
            
            if ($request->has('answers') && is_array($request->answers)) {
                foreach ($request->answers as $questionId => $answer) {
                    
                    // <-- TAMBAHAN: Ambil data remarks (optional) berdasarkan ID Pertanyaan
                    $remarks = isset($request->remarks[$questionId]) ? $request->remarks[$questionId] : null;

                    // Ambil tipe pertanyaan dari database untuk menentukan tempat simpan jawaban
                    $question = DB::connection('mysql')->table('mgr.survey_questions')
                        ->where('id', $questionId)
                        ->first();

                    if ($question) {
                        if ($question->question_type == 'multiple_choice') {
                            $answersData[] = [
                                'respondent_id' => $respondentId,
                                'question_id' => $questionId,
                                'option_id' => $answer, 
                                'essay_answer' => null,
                                'remarks' => $remarks, // <-- TAMBAHAN: Masukkan remarks ke array
                                'created_at' => $waktuSekarang,
                            ];
                        } else {
                            $answersData[] = [
                                'respondent_id' => $respondentId,
                                'question_id' => $questionId,
                                'option_id' => null,
                                'essay_answer' => $answer, 
                                'remarks' => null, // <-- TAMBAHAN: Untuk essay, remarks dikosongkan saja
                                'created_at' => $waktuSekarang,
                            ];
                        }
                    }
                }
            }

            // 3. Insert Semua Jawaban (Bulk Insert)
            if (!empty($answersData)) {
                DB::connection('mysql')->table('mgr.survey_answers')->insert($answersData);
            }

            // Validasi & Simpan permanen ke database
            DB::connection('mysql')->commit();

            return response()->json([
                'status' => 'OK', 
                'pesan' => __('tenant/survey.saved')
            ]);

        } catch (Exception $e) {
            // Jika ada error, batalkan semua proses insert sebelumnya
            DB::connection('mysql')->rollBack();
            
            return response()->json([
                'status' => 'Failed', 
                'pesan' => __('tenant/survey.system_error', ['message' => $e->getMessage()])
            ]);
        }
    }
}