<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BadgeModel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:View role|Create role|Update role|Delete role|Add user-role|Update user-role|Remove user-role', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create role', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update role', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete role', ['only' => ['destroy', 'removeuserRole']]);
        $this->middleware('permission:Update user-role', ['only' => ['adduser', 'updateuserrole']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = "Role Management";

        $roles = Role::orderBy('name', 'DESC')->get();
        $role  = Role::orderBy('name', 'DESC')->get();

        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title) {
            $array[] = $title->title;
        }

        $ar = implode(',', $array);
        $ex = explode(',', $ar);

        return view('roles.index', compact('role'), compact('roles'), compact('permission'))
            ->with('perm_title', $ex)
            ->with('pagetitle', $pagetitle);
    }

    public function create(): View
    {
        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pagetitle = "Role Management";

        $this->validate($request, [
            'name'           => 'required|unique:roles,name',
            'permission'     => 'required|array',
            'permission.*'   => 'exists:permissions,id',
            'title'          => 'nullable|string',
            'badge'          => 'nullable|string',
        ]);

        $role = Role::create([
            'name'  => $request->input('name'),
            'title' => $request->input('title'),
            'badge' => $request->input('badge'),
        ]);

        $permissionIds = $request->input('permission');
        $permissions   = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function show($id)
    {
        $pagetitle = "Role Management";

        $userRoleCount = DB::table('model_has_roles')->where('role_id', $id)->count();

        $usersWithRole = User::leftJoin("roles", "roles.id", "=", "users.id")
            ->join("model_has_roles", "model_has_roles.model_id", "=", "users.id")
            ->where("model_has_roles.role_id", $id)
            ->select([
                'users.id as id',
                'users.name as username',
                'users.email as email',
                'users.created_at as created_at',
                'model_has_roles.role_id as roleid'
            ])
            ->paginate(500);

        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();
        $rolePermissions2 = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title) {
            $array[] = $title->title;
        }

        $ar = implode(',', $array);
        $ex = explode(',', $ar);

        Session::put('role_url', request()->fullUrl());

        return view('roles.show', compact(
            'role',
            'rolePermissions',
            'rolePermissions2',
            'usersWithRole',
            'userRoleCount',
            'pagetitle'
        ))->with('perm_title', $ex);
    }

    public function edit($id): View
    {
        $pagetitle = "Role Management";

        $role = Role::find($id);
        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title) {
            $array[] = $title->title;
        }

        $ar = implode(',', $array);
        $ex = explode(',', $ar);

        return view('roles.edit', compact('role', 'permission', 'rolePermissions'))
            ->with('perm_title', $ex)
            ->with('pagetitle', $pagetitle);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $pagetitle = "Role Management";

        $this->validate($request, [
            'name'         => 'required|unique:roles,name,' . $id,
            'permission'   => 'required|array',
            'permission.*' => 'exists:permissions,id',
            'badge'        => 'nullable|string',
        ]);

        $role = Role::findOrFail($id);
        $role->update([
            'name'  => $request->input('name'),
            'badge' => $request->input('badge'),
        ]);

        $permissionIds = $request->input('permission');
        $permissions   = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
        $role->syncPermissions($permissions);

        if (session('role_url')) {
            return redirect(session('role_url'))
                ->with('success', 'Role Updated successfully')
                ->with('pagetitle', $pagetitle);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role Updated successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function adduser($id): View
    {
        $pagetitle = "Role Management";

        $role = Role::find($id);
        $r    = $role->name;
        $users = User::whereDoesntHave('roles', function ($q) use ($r) {
            $q->where('name', $r);
        })->get();

        return view('roles.adduser')
            ->with('role', $role)
            ->with('users', $users)
            ->with('pagetitle', $pagetitle);
    }

    /**
     * Assign role(s) to users.
     * When the student role is assigned, auto-populate users.student_id
     * from studentRegistration (matched by email, then by name).
     */
    public function updateuserrole(Request $request): RedirectResponse
    {
        $pagetitle = "Role Management";

        $this->validate($request, [
            'users'    => 'required|array',
            'users.*'  => 'exists:users,id',
            'roleid'   => 'required|exists:roles,id',
        ]);

        $role    = Role::findOrFail($request->input('roleid'));
        $userIds = $request->input('users');

        $isStudentRole = strtolower($role->name) === 'student';

        foreach ($userIds as $userId) {
            $user = User::findOrFail($userId);
            $user->assignRole($role->name);

            // When assigning the student role, link the user to studentRegistration
            if ($isStudentRole && is_null($user->student_id)) {
                $this->linkUserToStudentRecord($user);
            }
        }

        return redirect()->route('roles.show', $role->id)
            ->with('success', 'Users added to role successfully')
            ->with('pagetitle', $pagetitle);
    }

    /**
     * Try to find the matching studentRegistration row for a user and
     * write its id into users.student_id.
     *
     * Matching priority:
     *   1. Email match (most reliable)
     *   2. Full name match  (CONCAT firstname + ' ' + lastname)
     */
    private function linkUserToStudentRecord(User $user): void
    {
        try {
            $studentReg = null;

            // 1 — try email match first
            if (!empty($user->email)) {
                $studentReg = DB::table('studentRegistration')
                    ->where('email', $user->email)
                    ->select('id')
                    ->first();
            }

            // 2 — fall back to full-name match
            if (!$studentReg && !empty($user->name)) {
                $studentReg = DB::table('studentRegistration')
                    ->whereRaw("TRIM(CONCAT(firstname, ' ', lastname)) = ?", [trim($user->name)])
                    ->select('id')
                    ->first();
            }

            if ($studentReg) {
                $user->update(['student_id' => $studentReg->id]);

                Log::info('Linked user to studentRegistration', [
                    'user_id'        => $user->id,
                    'user_name'      => $user->name,
                    'student_reg_id' => $studentReg->id,
                ]);
            } else {
                Log::warning('Could not find matching studentRegistration for user', [
                    'user_id'   => $user->id,
                    'user_name' => $user->name,
                    'email'     => $user->email,
                ]);
            }
        } catch (\Exception $e) {
            // Non-fatal — role was still assigned, just log the failure
            Log::error('Error linking user to studentRegistration', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove user from role.
     */
    public function removeuserrole(Request $request, $userid, $roleid): JsonResponse
    {
        Log::info("Removing user role", ['user_id' => $userid, 'role_id' => $roleid]);

        try {
            $user = User::findOrFail($userid);
            $role = Role::findOrFail($roleid);
            $user->removeRole($role->name);

            return response()->json(['success' => true, 'message' => 'User role removed successfully']);
        } catch (\Exception $e) {
            Log::error("Error removing user role", [
                'error'   => $e->getMessage(),
                'user_id' => $userid,
                'role_id' => $roleid,
                'trace'   => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error removing user role: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        $delete = User::destroy($id);

        if ($delete == 1) {
            $success = true;
            $message = "User deleted successfully";
        } else {
            $success = true;
            $message = "User not found";
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function destroy($id): RedirectResponse
    {
        $pagetitle = "Role Management";

        DB::table("roles")->where('id', $id)->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully')
            ->with('pagetitle', $pagetitle);
    }

    public function bulkRemoveUsers(Request $request)
    {
        try {
            $request->validate([
                'role_id'          => 'required|exists:roles,id',
                'selected_users'   => 'required|array',
                'selected_users.*' => 'exists:users,id',
            ]);

            $role  = Role::findOrFail($request->role_id);
            $users = User::whereIn('id', $request->selected_users)->get();

            $removedCount = 0;
            $removedNames = [];

            foreach ($users as $user) {
                if ($user->hasRole($role->name)) {
                    $user->removeRole($role);
                    $removedCount++;
                    $removedNames[] = $user->name;
                }
            }

            if ($removedCount > 0) {
                return response()->json([
                    'success'       => true,
                    'message'       => "Successfully removed {$removedCount} user(s) from the {$role->name} role.",
                    'removed_count' => $removedCount,
                    'removed_users' => $removedNames,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No users were removed. They may not have had this role.',
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk remove users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove users: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getRoleUsers(Role $role, Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $page    = $request->get('page', 1);

            $usersWithRole = $role->users()
                ->with(['student.currentClass.class', 'staffemploymentDetails'])
                ->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

            $userRoleCount = $role->users()->count();

            if ($request->ajax()) {
                $html = view('roles.partials.users_table_rows', [
                    'users' => $usersWithRole,
                    'role'  => $role,
                ])->render();

                $pagination = view('roles.partials.pagination', [
                    'users' => $usersWithRole,
                ])->render();

                return response()->json([
                    'success'      => true,
                    'html'         => $html,
                    'pagination'   => $pagination,
                    'total'        => $userRoleCount,
                    'current_page' => $usersWithRole->currentPage(),
                    'last_page'    => $usersWithRole->lastPage(),
                    'per_page'     => $usersWithRole->perPage(),
                ]);
            }

            return view('roles.show', compact('role', 'usersWithRole', 'userRoleCount'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load users: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to load users: ' . $e->getMessage());
        }
    }
}
