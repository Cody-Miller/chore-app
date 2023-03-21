<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Action;

class ActionController extends Controller
{
    public function index() {
        return view('action.index', [
            'actions' => Action::latest()->paginate(3)
        ]);
    }
}
