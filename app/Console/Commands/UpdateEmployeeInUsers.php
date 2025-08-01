<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateEmployeeInUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-employee-in-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Employee In Users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Employee::where('login_status', 0)->whereNotNull('mobile_number')->chunk(100, function ($employees) {
            foreach ($employees as $employee) {

                $user = User::create([
                    'name'          => $employee->fname . " " . $employee->mname . " " . $employee->lname,
                    'employee_id'   => $employee->id,
                    'email'         => strtolower($employee->fname).$employee->employee_id.'@gmail.com',
                    'mobile'        => $employee?->mobile_number,
                    'password'      => Hash::make('12345678'),
                ]);


                DB::table('model_has_roles')->insert(['role_id' => 5, 'model_type' => 'App\Models\User', 'model_id' => $user->id]);

                $employee->update(['login_status' => 1]);
            }
        });

        $this->info('Users updated successfully.');
    }




}
