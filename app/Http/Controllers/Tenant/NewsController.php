<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
	public function index()
	{
		$datanewsfeed = DB::table('mgr.newsfeed')
			->where('status', 1)
			->whereDate('start_date', '<=', now())
			->whereDate('end_date', '>=', now())
			->orderBy('id', 'desc')
			->get();

		return view('tenant.news.index', [
			'datanewsfeed' => $datanewsfeed
		]);
	}
}