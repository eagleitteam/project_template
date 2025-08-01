<?php

namespace App\Console\Commands;

use App\Models\DaDifferance;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeDaDifferance;
use App\Models\FreezeAttendance;
use App\Models\OldEmployeeAllowance;
use App\Models\OldEmployeeSalary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreDADifferance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-d-a-differance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daDifferance = DaDifferance::where('status', 0)->first();

        if ($daDifferance) {
            Employee::has('salary')
                ->latest()
                ->chunk(1000, function ($employees) use ($daDifferance) {
                    foreach ($employees as $employee) {

                        // Fetch Freeze Attendance Data for the Employee
                        $getFreezeData = FreezeAttendance::where('employee_id', $employee->id)
                            ->where('month', '>=', $daDifferance->applicable_month)
                            ->where('month', '<', $daDifferance->given_month)
                            ->get();

                        // Calculate the DA difference for a single month
                        $differance_in_percent_single = ($daDifferance->DA_newRate - $daDifferance->DA_currentRate);

                        // Initialize difference calculation
                        $calculate_differance = 0;
                        foreach ($getFreezeData as $val) {
                            if ($val->basic_salary != 0) {
                                // Sum up the calculated difference for each freeze attendance
                                $calculate_differance += ($val->basic_salary * $differance_in_percent_single) / 100;
                            }
                        }

                        // Create Employee DA Difference record
                        EmployeeDaDifferance::create([
                            'da_differance_id'   => $daDifferance->id,
                            'employee_id'        => $employee->id,
                            'Emp_Code'           => $employee->employee_id,
                            'DA_currentRate'     => $daDifferance->DA_currentRate,
                            'DA_newRate'         => $daDifferance->DA_newRate,
                            'given_month'        => $daDifferance->given_month,
                            'no_of_month'        => $daDifferance->no_of_month,
                            'differance'         => $calculate_differance,
                            'financial_year_id'  => $daDifferance->financial_year_id,
                        ]);

                        // Update the Employee Allowance
                        $employee_allowance = EmployeeAllowance::where('allowance_id', 1)
                            ->where('employee_id', $employee->id)
                            ->latest()
                            ->first();

                        if ($employee_allowance) {
                            $employee_allowance->update([
                                'allowance_amt' => $daDifferance->DA_newRate,
                                'updated_at'    => now()
                            ]);
                        }

                        // Fetch and update Old Employee Allowance record
                        $get_old_allowance_data = OldEmployeeAllowance::where('employee_id', $employee->id)
                            ->where('allowance_id', 1)
                            ->latest()
                            ->first();

                        $get_old_employee_salary = OldEmployeeSalary::with('employee')
                            ->where('employee_id', $employee->id)
                            ->latest()
                            ->first();

                        if ($get_old_allowance_data) {
                            $get_old_allowance_data->update([
                                'is_active' => 0,
                                'end_date' => date('Y-m-d'),
                            ]);

                            $is_active = $employee_allowance && $employee_allowance->is_active == 1 ? 1 : 0;

                            // Create a new record in OldEmployeeAllowance
                            OldEmployeeAllowance::create([
                                'old_employee_salary_id' => $get_old_employee_salary->id,
                                'allowance_id'           => 1,
                                'is_active'              => $is_active,
                                'allowance_amt'          => $daDifferance->DA_newRate,
                                'allowance_type'         => 2,
                                'employee_id'            => $employee->id,
                                'Emp_Code'               => $employee->employee_id,
                                'applicable_date'        => date('Y-m-d'),
                            ]);
                        }
                    }
                });

            // Mark DA Difference as processed
            DB::table('da_differances')
                ->where('id', $daDifferance->id)
                ->update([
                    'status'     => 1,
                    'updated_at' => now(),
                ]);

            // Update the DA Allowance rate
            DB::table('allowances')
                ->where('id', 1)
                ->update([
                    'amount'     => $daDifferance->DA_newRate,
                    'updated_at' => now(),
                ]);

            $this->info('DA Difference stored successfully.');
        }
    }

    // public function handle()
    // {
    //     $daDifferance = DaDifferance::where('status', 0)->first();

    //     if ($daDifferance) {
    //         Employee::has('salary')
    //             ->latest()
    //             ->chunk(1000, function ($employees) use ($daDifferance) {
    //                 foreach ($employees as $employee) {

    //                     $getFreezeData = FreezeAttendance::where('employee_id',$employee->id)
    //                                     ->where('month','>=',$daDifferance->applicable_month)
    //                                     ->where('month','<',$daDifferance->given_month)->get();

    //                     $differance_in_percent_single   = ($daDifferance->DA_newRate - $daDifferance->DA_currentRate);
    //                     $differance_in_percent          = ($daDifferance->DA_newRate - $daDifferance->DA_currentRate) * $daDifferance->no_of_month;
    //                     // $calculate_differance           = ($old_emp_salary_basic->basic_salary * $differance_in_percent) / 100;

    //                     $calculate_differance = 0;
    //                     foreach($getFreezeData as $val)
    //                     {
    //                         if($val->basic_salary != 0)
    //                         {
    //                             $calculate_differance  = ($val->basic_salary * $differance_in_percent_single) / 100;

    //                         }
    //                     }
    //                     // $old_emp_salary_basic = OldEmployeeSalary::with('employee')->where('employee_id', $employee->id)
    //                     // ->whereNotNull('end_date')->where('basic_salary','!=',0)->latest()->first();

    //                     // if(!empty($old_emp_salary_basic)){

    //                     EmployeeDaDifferance::create([
    //                         'da_differance_id' => $daDifferance->id,
    //                         'employee_id' => $employee->id,
    //                         'Emp_Code' => $employee->employee_id,
    //                         'DA_currentRate' => $daDifferance->DA_currentRate,
    //                         'DA_newRate' => $daDifferance->DA_newRate,
    //                         'given_month' => $daDifferance->given_month,
    //                         'no_of_month' => $daDifferance->no_of_month, // Removed json_encode
    //                         'differance' => $calculate_differance,
    //                         'financial_year_id' => $daDifferance->financial_year_id,
    //                     ]);

    //                     $employee_allowance = EmployeeAllowance::where('allowance_id', 1)->where('employee_id', $employee->id)->latest()->first();

    //                     DB::table('employee_allowances')
    //                         ->where('allowance_id', 1)
    //                         ->where('employee_id', $employee->id)
    //                         ->update([
    //                             'allowance_amt' => $daDifferance->DA_newRate,
    //                             'updated_at' => now()
    //                         ]);

    //                     $get_old_allowance_data = OldEmployeeAllowance::where('employee_id', $employee->id)
    //                         ->where('allowance_id', 1)->latest()->first();

    //                     $get_old_employee_salary = OldEmployeeSalary::with('employee')->where('employee_id', $employee->id)->latest()->first();

    //                     if ($get_old_allowance_data) {
    //                         $get_old_allowance_data->update([
    //                             'is_active' => 0,
    //                             'end_date' => date('Y-m-d')
    //                         ]);

    //                         $is_active = $employee_allowance->is_active == 1 ? 1 : 0;

    //                         $allowanceData = [
    //                             'old_employee_salary_id' => $get_old_employee_salary->id,
    //                             'allowance_id' => 1,
    //                             'is_active' => $is_active,
    //                             'allowance_amt' => $daDifferance->DA_newRate,
    //                             'allowance_type' => 2,
    //                             'employee_id' => $employee->id,
    //                             'Emp_Code' => $employee->employee_id,
    //                             'applicable_date' => date('Y-m-d'),
    //                         ];

    //                         OldEmployeeAllowance::create($allowanceData);
    //                     }
    //                 // }
    //             }
    //             });

    //         DB::table('da_differances')
    //             ->where('id', $daDifferance->id)
    //             ->update([
    //                 'status' => 1,
    //                 'updated_at' => now()
    //             ]);

    //         DB::table('allowances')
    //             ->where('id', 1)
    //             ->update([
    //                 'amount' => $daDifferance->DA_newRate,
    //                 'updated_at' => now()
    //             ]);

    //         $this->info('DA Difference stored successfully.');
    //     }
    // }

}
