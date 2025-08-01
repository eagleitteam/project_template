<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeLeaves;
use App\Models\LeaveType;
use Illuminate\Console\Command;

class StoreEmployeeLeaveMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-employee-leave-master';

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
        $employees = Employee::get();
        $leaveTypes = LeaveType::get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // Check if employee leave data already exists
                $employeeLeave = EmployeeLeaves::where('employee_id', $employee->id)
                                            ->where('leave_type_id', $leaveType->id)
                                            ->first();

                if ($employeeLeave) {
                    // If exists, update leave count by adding 300
                    $employeeLeave->update([
                        'no_of_leaves' => $employeeLeave->no_of_leaves,
                    ]);
                } else {
                    // If not exists, insert new leave data with 300 leaves
                    EmployeeLeaves::create([
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'carry_forward' => $leaveType->carry_forward,
                        'no_of_leaves'  => $leaveType->no_of_leaves, // Insert with 300 leaves
                        'encashable'    => $leaveType->encashable,
                    ]);
                }
            }
        }
    }
}
