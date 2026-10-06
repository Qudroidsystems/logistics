<?php

namespace App\Http\Controllers;

use App\Models\schoolhouse;
use Illuminate\Http\Request;
use App\Models\Studenthouse;
use App\Models\Student;

class StudentHouseController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:add-studenthouse', ['only' => ['index','store']]);
         $this->middleware('permission:add-studenthouse', ['only' => ['create','store']]);
         $this->middleware('permission:add-studenthouse', ['only' => ['edit','update']]);

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
        //
        $id = $request->post('id');
        $schoolhouseid = $request->post('schoolhouseid');
        Studenthouse::where("studentid", $id)->update(["schoolhouse" => $schoolhouseid]);

        return back() ->with('success','You have successfully added Student a School  House.');

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

        $student = Studenthouse::find($id);

        $schoolhouse = schoolhouse::all();
        return view('studenthouse.index')->with('student',$student)
                                         ->with('schoolhouse',$schoolhouse);
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
