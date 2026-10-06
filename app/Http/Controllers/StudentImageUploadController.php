<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Studentpicture;
use App\Models\student;
use File;
use Illuminate\Support\Facades\Storage;
use App\Traits\ImageManager as TraitsImageManager;

class StudentImageUploadController extends Controller
{

    use TraitsImageManager;

    function __construct()
    {
         $this->middleware('permission:student_picture-upload', ['only' => ['index','store']]);
         $this->middleware('permission:student_picture-upload', ['only' => ['create','store']]);
         $this->middleware('permission:student_picture-upload', ['only' => ['edit','update']]);

    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        // $request->validate([

        //     'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        // ]);



        // $imageName = time().'.'.$request->image->extension();



        // $request->image->move(public_path('images/studentavatar'), $imageName);
        // $id = $request->post('id');


        // /* Store $imageName name in DATABASE from HERE */

        // Studentpicture::where("studentid", $id)->update(["picture" => $imageName]);

        // return back() ->with('success','You have successfully uploaded Student image.');
        //               //->with('image',$imageName);


                      $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', ]);
                      $path = storage_path('images/studentavatar');
                      !is_dir($path) && mkdir($path, 0775, true);


                       if( $file = $request->file('image')) {
                          $filename = $request->fname.'_'. $request->lname;

                                  if ( Studentpicture::where('id',$request->id)->whereNotNull('picture')->get()) {

                                      $filename = Studentpicture::where('id',$request->id)->get(['picture']);
                                      foreach ($filename as $key => $value) {
                                        $datafile = $value->avatar;
                                      }


                                      if( Storage::disk('public')->exists('images/studentavatar/'.$datafile)){
                                         Storage::disk('public')->delete('images/studentavatar/'.$datafile);
                                         $this->studentuploads($file,$path,$request->id);
                                      }else{
                                          $this->studentuploads($file,$path,$request->id);
                                          // dd('File does not exist.');
                                      }
                                  }else {

                                      echo "something went wrong";
                                          }


                          return back() ->with('success','You have successfully uploaded Student image.');
                      }
                      else{
                        echo "something went wrong";
                      }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $picture = Studentpicture::find($id);

        return view('studentImageUpload.index')->with('picture',$picture);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
