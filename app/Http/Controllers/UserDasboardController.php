<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserDasboardController extends Controller
{
   
    
    public function index()
    {
      
          return view('dashboard');
    
    }
}
