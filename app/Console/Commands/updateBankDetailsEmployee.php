<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateBankDetailsEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-bank-details-employee';

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
        DB::transaction(function () {
            Employee::chunk(1000, function ($employees) {
                foreach ($employees as $employee) {

                    // Fetch the corresponding test_employee based on emp_code
                    $test_employee = DB::table('test_employee')->where('emp_code', $employee->employee_id)->first();

                    if ($test_employee) {
                        // Update the employee data
                        $employee->update([
                            'bank_id'   => ($employee->ward_id == 72) ? 2 : 1,
                            'account_no' => $test_employee->bank_account_number,
                            'aadhar'    => $test_employee->aadhar,
                        ]);

                        // Update the status of test_employee
                        DB::table('test_employee')
                            ->where('emp_code', $employee->employee_id)
                            ->update(['status' => 1]);
                    }
                }
            });
        });

    }
}
