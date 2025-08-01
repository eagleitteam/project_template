<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\EmployeeLoan;
use App\Models\EmployeeMonthlyLoan;
use App\Models\FinancialYear;

class StoreEmployeeMonthlyLoans extends Command
{
    protected $signature = 'app:store-employee-monthly-loans';
    protected $description = 'Store employee monthly loans';

    public function handle()
    {

        $financial_year = FinancialYear::where('is_active', 1)->first();

        $month = date('m');

        if ($financial_year) {
            if ($month <= 03) {
                $year = date('Y', strtotime($financial_year->to_date));
            } else {
                $year = date('Y', strtotime($financial_year->from_date));
            }
        }

        $fromDate = Carbon::parse($year . '-' . ($month) . '-' . 16);
        $toDate = clone ($fromDate);
        $fromDate = (string) $fromDate->startOfMonth()->toDateString();
        $toDate = (string) $toDate->endOfMonth()->toDateString();

        $currentDate = Carbon::today();

        $employeeLoans = EmployeeLoan::with('employee')
            ->where('status', 1)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->get();

        if (!empty($employeeLoans)) {
            foreach ($employeeLoans as $employeeLoan) {
                $existingLoan = EmployeeMonthlyLoan::where('from_date', $fromDate)
                    ->where('to_date', $toDate)
                    ->where('loan_id', $employeeLoan->loan_id)
                    ->where('employee_id', $employeeLoan->employee_id)
                    // ->where('installment_amount', $employeeLoan->instalment_amount)
                    ->exists();

                if (!$existingLoan) {
                    EmployeeMonthlyLoan::create([
                        'employee_id' => $employeeLoan->employee_id,
                        'Emp_Code' => $employeeLoan->Emp_Code,
                        'emp_name' =>  $employeeLoan?->employee->fname . " " . $employeeLoan?->employee->mname . " " . $employeeLoan?->employee->lname,
                        'employee_loan_id' => $employeeLoan->id,
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                        'loan_id' => $employeeLoan->loan_id,
                        'installment_amount' => $employeeLoan->instalment_amount,
                        'installment_no' => $employeeLoan->deducted_instalment + 1,
                        'financial_year_id' => $employeeLoan->financial_year_id,
                    ]);

                    $employeeLoan->increment('deducted_instalment');
                    $employeeLoan->decrement('pending_instalment');
                }
            }

            $this->info('Employee monthly loans stored successfully.');
        }
    }
}
