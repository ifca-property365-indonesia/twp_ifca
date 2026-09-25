<?php

use App\Http\Controllers\Admin\WsbangunController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Gabungan routes/api.php webadmin + webtenant. Tetap di /api/... (tanpa
| prefix portal) supaya integrasi eksternal (mis. Wsbangun) tidak berubah.
|
| Simpan ticket (dulu /api/ticket/save) dan grafik meter (dulu /api/dash/getGraphMeterId)
| dipindah ke routes/tenant.php: keduanya butuh login tenant (session).
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- dari webadmin ---
Route::post('/business/{method}/{value}', [WsbangunController::class, 'business']);
Route::post('/ticket/update/{params}', [WsbangunController::class, 'update_ticket']);
