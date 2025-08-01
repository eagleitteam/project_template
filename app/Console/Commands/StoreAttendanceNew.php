<?php

namespace App\Console\Commands;

use App\Models\AddEmployeeLeave;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StoreAttendanceNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-attendance-new';

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
        $financial_year = FinancialYear::where('is_active', 1)->first();

        if (!$financial_year) {
            $this->info('No active financial year found.');
            return;
        }
        // $month = date('m');
        $month = 9;
        $year = ($month <= 3) ? date('Y', strtotime($financial_year->to_date)) : date('Y', strtotime($financial_year->from_date));

        $fromDate = Carbon::parse("$year-$month-01")->startOfMonth()->toDateString();
        $toDate = Carbon::parse("$year-$month-01")->endOfMonth()->toDateString();

        // Convert to Carbon instances
        $fromDateCarbon = Carbon::parse($fromDate);
        $toDateCarbon = Carbon::parse($toDate);

        // Calculate the difference in days
        $numberOfDays = $fromDateCarbon->diffInDays($toDateCarbon) + 1;

        $employees = Employee::get();

        foreach ($employees as $employee) {
            // Check if attendance already exists for this employee, month, and financial year
            $check_attendance_exist = Attendance::where('employee_id', $employee->id)
                ->where('from_date', $fromDate)
                ->where('to_date', $toDate)
                ->exists();

            if (!$check_attendance_exist) {
                $check_leave_upload = AddEmployeeLeave::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('financial_year_id', $financial_year->id)
                    ->sum('no_of_days');

                $total_present_days = $numberOfDays;

                if ($check_leave_upload != 0) {
                    $total_present_days = $numberOfDays - $check_leave_upload;
                }

                // Create attendance record if it doesn't exist
                Attendance::create([
                    'employee_id'        => $employee->id,
                    'Emp_Code'           => $employee->employee_id,
                    'emp_name'           => $employee->fname . ' ' . $employee->mname . ' ' . $employee->lname,
                    'from_date'          => $fromDate,
                    'to_date'            => $toDate,
                    'main_present_days'  => $numberOfDays,
                    'total_present_days' => $total_present_days,
                    'total_leave'        => $check_leave_upload,
                    'month'              => $month,
                    'financial_year_id'  => $financial_year->id,
                ]);

                $this->info('Attendance for ' . $employee->fname . ' ' . $employee->lname . ' stored successfully.');
            } else {
                $this->info('Attendance for ' . $employee->fname . ' ' . $employee->lname . ' already exists.');
            }
        }
    }

}
