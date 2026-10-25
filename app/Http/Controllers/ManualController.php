<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    /**
     * システム操作マニュアル画面を表示する
     */
    public function index(Request $request): View
    {
        return view('manual.index');
    }
}
