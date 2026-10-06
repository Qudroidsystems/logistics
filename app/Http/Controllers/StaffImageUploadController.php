<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffPicture;

class StaffImageUploadController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:staff-picture-upload', ['only' => ['index','store']]);
         $this->middleware('permission:staff-picture-upload', ['only' => ['create','store']]);
         $this->middleware('permission:staff-picture-upload', ['only' => ['edit','update']]);

    }

    public function imageUploadPost(Request $request)

    {

        $request->validate([

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);



        $imageName = time().'.'.$request->image->extension();



        $request->image->move(public_path('images/staffpics'), $imageName);
        $id = $request->post('id');


        /* Store $imageName name in DATABASE from HERE */

        Staffpicture::where("staffId", $id)->update(["picture" => $imageName]);

        return back() ->with('success','You have successfully uploaded Staff image.');
                      //->with('image',$imageName);

    }
}
