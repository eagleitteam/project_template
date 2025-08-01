<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRetirementDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-retirement-date';

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
        Employee::with('class')->whereNotNull('dob')->chunk(100, function ($employees) {
            foreach ($employees as $employee) {
                if ($employee->dob) {
                    // Calculate retirement date at the age of 58
                    $retirementDate = Carbon::parse($employee->dob)->addYears($employee?->class?->working_year);

                    // Check if the retirement date is the first day of the month
                    if ($retirementDate->toDateString() === $retirementDate->startOfMonth()->toDateString()) {
                        // Set retirement date to the last day of the previous month
                        $employee->retirement_date = $retirementDate->subMonth()->endOfMonth();
                    } else {
                        // Set retirement date to the last day of the same month
                        $employee->retirement_date = $retirementDate->endOfMonth();
                    }

                    // Save the updated employee data
                    $employee->save();
                }
            }
        });

    }
}
