<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Inspiring;
use App\Models\FinancialYear;
use App\Models\Ward;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function showLogin()
    {
        $quotes = [];
        for ($i = 1; $i <= 3; $i++) {
            array_push($quotes, Inspiring::quote());
        }

        $financialYear = FinancialYear::latest()->get();

        return view('admin.auth.login')->with(['quotes' => $quotes, 'financialYear' => $financialYear]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'username'          => 'required',
                'password'          => 'required',
            ],
            [
                'username.required'         => 'Please Enter Username',
                'password.required'         => 'Please Enter Password',
            ]
        );

        if ($validator->passes()) {
            $username = $request->username;
            $password = $request->password;
            $remember_me = $request->has('remember_me') ? true : false;

            $financialYear = FinancialYear::where('id', $request->financial_year)->latest()->first();

            try {
                $user = User::where('mobile', $username)->first();

                if (!$user)
                    return response()->json(['error2' => 'No user found with this username']);

                if ($user->active_status == '0' && !$user->roles)
                    return response()->json(['error2' => 'You are not authorized to login, contact HOD']);

                if (!auth()->attempt(['mobile' => $username, 'password' => $password], $remember_me))
                    return response()->json(['error2' => 'Your entered credentials are invalid']);


                if (Auth::user()->hasRole(['Ward HOD'])) {
                    $ward = Ward::find(Auth::user()->ward_id);

                    session(['ward_name' => $ward ? $ward->name : '']);
                } elseif (Auth::user()->hasRole(['Department HOD'])) {
                    $ward = Ward::find(Auth::user()->ward_id);
                    $department = Department::find(Auth::user()->department_id);

                    session(['ward_name' => $ward ? $ward->name : '']);
                    session(['departmentname' => $ward ? $department->name : '']);
                } else {
                    session()->forget('ward_name');
                    session()->forget('departmentname');
                }


                $userType = '';
                if ($user->hasRole(['User']))
                    $userType = 'user';

                if ($user->hasRole(['Employee']))
                    $userType = 'employee';

                return response()->json(['success' => 'login successful', 'user_type' => $userType, 'user'=> $user]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info("login error:" . $e);
                return response()->json(['error2' => 'Something went wrong while validating your credentials!']);
            }
        } else {
            return response()->json(['error' => $validator->errors()]);
        }
    }

    public function logout()
    {
        auth()->logout();

        return redirect()->route('login');
    }


    public function showChangePassword()
    {
        return view('admin.auth.change-password');
    }


    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => [
                'required',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            ],
            'confirm_password' => 'required|same:password',
        ], [
            'password.regex' => 'The password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        if ($validator->passes()) {
            $old_password = $request->old_password;
            $password = $request->password;

            try {
                $user = DB::table('users')->where('id', $request->user()->id)->first();

                if (Hash::check($old_password, $user->password)) {
                    DB::table('users')->where('id', $request->user()->id)->update([
                                                                            'password'      => Hash::make($password),
                                                                            'updated_by'    => Auth::user()->id,
                                                                            'updated_at'    => now(),

                                                                            ]);


                    $userType = '';
                    if (Auth::user()->hasRole(['User']))
                        $userType = 'user';

                    if (Auth::user()->hasRole(['Employee']))
                        $userType = 'employee';

                    return response()->json(['success' => 'Password changed successfully!', 'user_type' => $userType]);
                } else {
                    return response()->json(['error2' => 'Old password does not match']);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info("password change error:" . $e);
                return response()->json(['error2' => 'Something went wrong while changing your password!']);
            }
        } else {
            return response()->json(['error' => $validator->errors()]);
        }
    }
}
