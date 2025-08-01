<?php

namespace App\Console\Commands;

use App\Models\EmployeeMonthlyLic;
use App\Models\FinancialYear;
use App\Models\LicDeduction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StoreEmployeeMonthlyLic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-employee-monthly-lic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store employee monthly loans';

    /**
     * Execute the console command.
     */
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

        $employeeLics = LicDeduction::with('employee')
            ->where('status', 1)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->get();

        if (!empty($employeeLics)) {
            foreach ($employeeLics as $employeeLic) {
                $existingLic = EmployeeMonthlyLic::where('from_date', $fromDate)
                    ->where('to_date', $toDate)
                    ->where('lic_deduction_id', $employeeLic->id)
                    ->where('employee_id', $employeeLic->employee_id)
                    ->exists();

                if (!$existingLic) {
                    EmployeeMonthlyLic::create([
                        'employee_id'           => $employeeLic->employee_id,
                        'Emp_Code'              => $employeeLic->Emp_Code,
                        'emp_name'              =>  $employeeLic?->employee->fname . " " . $employeeLic?->employee->mname . " " . $employeeLic?->employee->lname,
                        'lic_deduction_id'      => $employeeLic->id,
                        'from_date'             => $fromDate,
                        'to_date'               => $toDate,
                        'installment_amt'       => $employeeLic->installment_amt,
                        'installment_no'        => $employeeLic->deducted_installment + 1,
                        'financial_year_id'     => $employeeLic->financial_year_id,
                    ]);

                    $employeeLic->increment('deducted_installment');
                    $employeeLic->decrement('pending_installment');
                }
            }

            $this->info('Employee monthly LIC stored successfully.');
        }
    }
}
