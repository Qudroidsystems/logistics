<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Academicinfo1;
use App\Models\Academicinfo2;
use App\Models\Academicinfo3;
use App\Models\Staffpicture;
use Illuminate\Http\Request;
use App\Models\User;


class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware('permission:staff-list|add-staff|edit-staff|delete-staff', ['only' => ['index','store']]);
         $this->middleware('permission:add-staff', ['only' => ['create','store']]);
         $this->middleware('permission:edit-staff', ['only' => ['edit','update']]);
         $this->middleware('permission:delete-staff', ['only' => ['destroy']]);
    }
    public function index()
    {



           $users = User::whereHas('roles', function($q){ $q->where('name', '!=','Student'); })
                         ->leftJoin('staffbioinfo', 'staffbioinfo.userid','=','users.id')
                         ->leftJoin('staffpicture', 'staffpicture.staffid','=','users.id')
                         ->get(['users.id as userid','users.name as name','staffpicture.picture as pic',
                         'users.email as email','staffbioinfo.employmentid as employmentid']);

         return view('staff.index',compact('users'));


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        //
        //$user = User::find($id);

       // $user = User::leftJoin('stafbioinfo', 'staffbioinfo.userid','=','user.id')
      //  ->leftJoin('staffpicture', 'staffpicture.userid','=','user.id')
       //  ->get(['user.role as role','staffbioinfo.userid as userid','stafpicture.picture as pic','staffbioinfo.email as email']);

       // return view('staff.create')->with('user',$user);
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

        staff::firstOrCreate( ['userid'=>$id], ['userid'=>$id] );
        Academicinfo1::firstOrCreate( ['staffid'=>$id], ['staffid'=>$id] );
        Academicinfo2::firstOrCreate( ['staffid'=>$id], ['staffid'=>$id] );
        Academicinfo3::firstOrCreate( ['staffid'=>$id], ['staffid'=>$id] );
        Staffpicture::firstOrCreate( ['staffId'=>$id], ['staffId'=>$id] );


       $staff = Staff::find($id);
       $acad1 = AcademicInfo1::find($id);
       $acad2 = AcademicInfo2::find($id);
       $acad3 = AcademicInfo3::find($id);
       $spic = StaffPicture::find($id);
       $staff2 = User::find($id);

        return view('staff.edit')->with('staff',$staff)
                                 ->with('staff2',$staff2)
                                 ->with('acad1',$acad1)
                                 ->with('acad2',$acad2)
                                 ->with('acad3',$acad3)
                                 ->with('spic',$spic);
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
        $input = $request->all();
        $staff = Staff::find($id);
        $acad1 = AcademicInfo1::find($id);
        $staff->update($input);
       return redirect()->route('staff.edit',[$id])
       ->with('success', 'Staff biodata has been updated successfully.');
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
