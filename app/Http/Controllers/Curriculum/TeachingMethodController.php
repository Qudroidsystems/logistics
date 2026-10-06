<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\TeachingMethod;
use Illuminate\Http\Request;

/** Admin-maintained library of teaching methods used in lesson notes. */
class TeachingMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage teaching methods');
    }

    public function index()
    {
        return view('curriculum.methods.index', [
            'pagetitle' => 'Teaching Methods',
            'methods'   => TeachingMethod::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);
        $data['is_active'] = true;
        TeachingMethod::create($data);
        return back()->with('success', 'Method added.');
    }

    public function update(Request $request, TeachingMethod $method)
    {
        $method->update($request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Method updated.');
    }

    public function destroy(TeachingMethod $method)
    {
        $method->delete();
        return back()->with('success', 'Method removed.');
    }
}
