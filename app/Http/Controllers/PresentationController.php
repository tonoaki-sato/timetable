<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PresentationController extends Controller
{
    /**
     * 幹事会向けプレゼンテーションスライド画面を表示する
     */
    public function index(Request $request): View
    {
        return view('presentation.index');
    }
}
