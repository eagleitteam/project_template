<?php

namespace App\Console\Commands;

use App\Models\EmployeeFestivalAdvance;
use App\Models\FestivalExcelUpload;
use Illuminate\Console\Command;

class AddFestivalDeductionFromExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-festival-deduction-from-excel';

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
        FestivalExcelUpload::with('employee')->where('cron_status', 0)->chunk(100, function ($employees) {
            foreach ($employees as $employee) {

                $user = EmployeeFestivalAdvance::create([
                    'employee_id'           => $employee->employee->id,
                    'Emp_Code'              => $employee->Emp_Code,
                    'festival_name'         => 'Diwali',
                    'total_amount'          => $employee->total,
                    'total_instalment'      => 10,
                    'deducted_instalment'   => 0,
                    'pending_instalment'    => 10,
                    'instalment_amount'     => ($employee->total / 10),
                    'applicable_month'      => 9,
                    'start_date'            => '2024-10-01',
                    'end_date'              => '2025-08-31',
                    'financial_year_id'     => 1,
                ]);


                $employee->update(['cron_status' => 1]);
            }
        });

        $this->info('Festival Deduction updated successfully.');
    }
}
