<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentViewController extends Controller
{
    //

   public function show1($id,$termid,$sessionid){

        dd([$id,$termid,$sessionid]);

    }


   public function show($id){
echo $id;

    }
}
