<?php

namespace App\Console\Commands;

use App\Models\EmployeeFestivalAdvance;
use App\Models\EmployeeMonthlyFestivalAdvance;
use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StoreEmployeeMonthlyFestivalAdvance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-employee-monthly-festival-advance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store employee monthly festival advance';

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

        $month = date('m');
        $year = ($month <= 3) ? date('Y', strtotime($financial_year->to_date)) : date('Y', strtotime($financial_year->from_date));

        $fromDate = Carbon::parse("$year-$month-16")->startOfMonth()->toDateString();
        $toDate = Carbon::parse("$year-$month-16")->endOfMonth()->toDateString();

        $currentDate = Carbon::today();

        $employeeFestivalAdvances = EmployeeFestivalAdvance::with('employee')
            // ->where('apply_status', 2)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->get();

        if ($employeeFestivalAdvances->isNotEmpty()) {
            foreach ($employeeFestivalAdvances as $employeeFestivalAdv) {
                $exists = EmployeeMonthlyFestivalAdvance::where('from_date', $fromDate)
                    ->where('to_date', $toDate)
                    ->where('employee_festival_advance_id', $employeeFestivalAdv->id)
                    ->where('employee_id', $employeeFestivalAdv->employee_id)
                    ->exists();

                if (!$exists) {
                    EmployeeMonthlyFestivalAdvance::create([
                        'employee_id'                => $employeeFestivalAdv->employee_id,
                        'Emp_Code'                   => $employeeFestivalAdv->Emp_Code,
                        'emp_name'                   => optional($employeeFestivalAdv->employee)->fname . ' ' . optional($employeeFestivalAdv->employee)->mname . ' ' . optional($employeeFestivalAdv->employee)->lname,
                        'employee_festival_advance_id' => $employeeFestivalAdv->id,
                        'from_date'                  => $fromDate,
                        'to_date'                    => $toDate,
                        'installment_amount'         => $employeeFestivalAdv->instalment_amount,
                        'installment_no'             => $employeeFestivalAdv->deducted_instalment + 1,
                        'financial_year_id'          => $employeeFestivalAdv->financial_year_id,
                    ]);

                    $employeeFestivalAdv->increment('deducted_instalment');
                    $employeeFestivalAdv->decrement('pending_instalment');
                }
            }

            $this->info('Employee monthly festival advances stored successfully.');
        } else {
            $this->info('No applicable employee festival advances found.');
        }
    }

}
