<?php

namespace App\Console\Commands;

use App\Models\Allowance;
use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeFestivalAdvance;
use App\Models\EmployeeMonthlyFestivalAdvance;
use App\Models\EmployeeMonthlyLoan;
use App\Models\EmployeeMonthlyLic;
use App\Models\EmployeeProvidentFund;
use App\Models\FreezeAttendance;
use App\Models\FreezeCron;
use App\Models\PayScale;
use App\Models\RemainingFreezeSalary;
use App\Models\SupplimentaryBill;
use App\Models\EmployeeDaDifferance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreezeEmployeeSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:freeze-employee-salary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Employee Salary';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Fetch the freeze record
        $freeze = FreezeCron::where('status', 0)->first();

        if ($freeze) {

            info('cron job run');

            DB::transaction(function () use ($freeze) {
                $ward_id = $freeze->ward_id;
                $from_date = $freeze->from_date;
                $to_date = $freeze->to_date;
                $month = $freeze->month;
                $financial_year_id = $freeze->financial_year_id;

                $startDate = Carbon::createFromFormat('Y-m-d', $from_date);
                $endDate = Carbon::createFromFormat('Y-m-d', $to_date);
                $numberOfDaysInMonth = $startDate->diffInDays($endDate);
                $numberOfDaysInMonth += 1;
                Employee::where('ward_id', $ward_id)
                    ->has('salary')
                    // ->where('id', 963)
                    ->where('activity_status', 1)
                    ->latest()
                    ->chunk(100, function ($employees) use (
                        $from_date,
                        $to_date,
                        $month,
                        $financial_year_id,
                        $numberOfDaysInMonth
                    ) {
                        foreach ($employees as $employee) {
                            // Process each employee
                            $this->processEmployee($employee, $from_date, $to_date, $month, $financial_year_id, $numberOfDaysInMonth);
                        }
                    });

                // Update freeze cron status
                DB::table('freeze_crons')
                    ->where('id', $freeze->id)
                    ->update([
                        'status' => 1,
                        'updated_at' => now()
                    ]);
            });

            $this->info('Employee salary stored successfully.');
        }
    }

    private function processEmployee($employee, $from_date, $to_date, $month, $financial_year_id, $numberOfDaysInMonth)
    {
        $da_differance = 0;
        $total_allowance = 0;
        $remaining_total_allowance = 0;

        $total_deduction = 0;
        $remaining_total_deduction = 0;

        $total_loan_deduction = 0;
        $total_loan_remaining_deduction = 0;

        $allowance_ids_array = [];
        $allowance_amt_array = [];
        $allowance_type_array = [];

        $remaining_allowance_ids_array = [];
        $remaining_allowance_amt_array = [];
        $remaining_allowance_type_array = [];

        $deduction_ids_array = [];
        $deduction_amt_array = [];
        $deduction_type_array = [];

        $remaining_deduction_ids_array = [];
        $remaining_deduction_amt_array = [];
        $remaining_deduction_type_array = [];

        $loan_ids_array = [];
        $loan_amt_array = [];
        $loan_deduction_bank_id = [];

        $STAMP_DUTY = 1;

        $Net_Salary = 0;
        $Remaining_Net_Salary = 0;

        $bncmc_share_da = 0;
        $remaining_bncmc_share_da = 0;
        $basicandDA = 0;
        $remainingbasicandDA = 0;
        $remaining_basicandDA = 0;
        $share = 0;
        $remaining_share = 0;
        $employee_share = 0;
        $employee_remaining_share = 0;

        $pf_loan = 0;
        $pf_contribution = 0;

        // LIC Deduction
        $lic_ids_array = [];
        $lic_amt_array = [];

        $total_lic_deduction = 0;
        $total_lic_remaining_deduction = 0;

        // festival advance Deduction
        $festadv_ids_array = [];
        $festadv_amt_array = [];
        $total_festAdv_deduction = 0;
        $total_festAdv_remaining_deduction = 0;

        $is_employee_retire_within_3_months = 0;
        // festival advance Allowance

        // $totalFestivalAdvance = 0;
        // $remainingtotalFestivalAdvance = 0;

        if ($employee->salary) {

            $is_employee_retire_within_3_months = Carbon::parse($employee->retirement_date)->lessThanOrEqualTo(Carbon::now()->addMonths(3));

            // Fetch loans for the employee within the given date range
            $loanArr = EmployeeMonthlyLoan::where('from_date', $from_date)
                ->where('to_date', $to_date)
                ->where('employee_id', $employee->id)
                ->get();

            // LIC Dedcution Data Get
            $licArr = EmployeeMonthlyLic::
            where('from_date', $from_date)
            ->where('to_date', $to_date)
            ->where('employee_id', $employee->id)
            ->get();

            // Festival advance Dedcution Data Get
            $festAdvArr = EmployeeMonthlyFestivalAdvance::
            where('from_date', $from_date)
            ->where('to_date', $to_date)
            ->where('employee_id', $employee->id)
            ->get();


            // Fetch pay scales for the employee within the given salary
            $payScales = PayScale::where('id', $employee->salary->pay_scale_id)
                ->first();

            $check_status = 0;
            if ($employee?->employee_status?->applicable_date > $from_date) {
            } else {
                $check_status = 1;
            }

            $get_present_days = Attendance::where('employee_id',$employee->id)
                                            ->where('from_date',$from_date)
                                            ->where('to_date',$to_date)->first();


            if(empty($get_present_days))
            {
                $present_days_new = 0;

            }else{
                $present_days_new = $get_present_days->total_present_days + $get_present_days->total_leave ;
            }

            // $present_days_new = $numberOfDaysInMonth;

            $basicSalary = optional($employee->salary)->basic_salary;
            $gradePay = optional($employee->salary)->grade_pay;
            $basicPlusGpay = $basicSalary + $gradePay;

            // Loan Logic
            foreach ($loanArr as $loan) {

                $loan_ids_array[] = $loan->id;
                $loan_amt_array[] = $loan->installment_amount;
                $loan_deduction_bank_id[] = $loan->loan_id;

                $total_loan_deduction += $loan->installment_amount;
                $total_deduction += $loan->installment_amount;

                if ($employee?->employee_status?->applicable_date > $from_date || ($present_days_new < $numberOfDaysInMonth && empty($employee?->employee_status))) {
                } else {
                    if ($employee?->employee_status?->is_salary_applicable == 0) {
                        $total_loan_remaining_deduction += $loan->installment_amount;
                        $remaining_total_deduction += $loan->installment_amount;
                    }
                }
                // To store pf loan
                if ($loan->id == 1) {
                    $pf_loan += $loan->installment_amount;
                }
            }

            $implode_loan_ids_array = implode(',', $loan_ids_array);
            $implode_loan_amt_array = implode(',', $loan_amt_array);
            $implode_loan_bank_array = implode(',', $loan_deduction_bank_id);

            // Loan Logic  End

            // LIC Logic
            foreach ($licArr as $lic) {
                $lic_ids_array[] = $lic->id;
                $lic_amt_array[] = $lic->installment_amt;

                $total_lic_deduction += $lic->installment_amt;
                $total_deduction += $lic->installment_amt;

                if ($employee?->employee_status?->applicable_date > $from_date || ($present_days_new < $numberOfDaysInMonth && empty($employee?->employee_status))) {
                } else {
                    if ($employee?->employee_status?->is_salary_applicable == 0) {
                        $total_lic_remaining_deduction += $lic->installment_amt;
                        $remaining_total_deduction += $lic->installment_amt;
                    }
                }
            }

            $implode_lic_ids_array = implode(',', $lic_ids_array);
            $implode_lic_amt_array = implode(',', $lic_amt_array);

            // LIC Logic End

            // Festival advance deduction logic start

            foreach ($festAdvArr as $festAdv) {
                $festadv_ids_array[] = $festAdv->id;
                $festadv_amt_array[] = $festAdv->installment_amount;

                $total_festAdv_deduction    += $festAdv->installment_amount;
                $total_deduction            += $festAdv->installment_amount;

                if ($employee?->employee_status?->applicable_date > $from_date || ($present_days_new < $numberOfDaysInMonth && empty($employee?->employee_status))) {
                } else {
                    if ($employee?->employee_status?->is_salary_applicable == 0) {
                        $total_festAdv_remaining_deduction  += $festAdv->installment_amount;
                        $remaining_total_deduction          += $festAdv->installment_amount;
                    }
                }
            }

            $implode_festAdv_ids_array = implode(',', $festadv_ids_array);
            $implode_festAdv_amt_array = implode(',', $festadv_amt_array);

            // Festival advance deduction logic end


            if ($employee?->employee_status?->applicable_date > $from_date || ($present_days_new < $numberOfDaysInMonth && empty($employee?->employee_status))) {
            } else {
                $implode_loan_remaining_ids_array = implode(',', $loan_ids_array);
                $implode_loan_remaining_amt_array = implode(',', $loan_amt_array);
                $implode_loan_remaining_bank_array = implode(',', $loan_deduction_bank_id);

                $implode_lic_remaining_ids_array = implode(',', $lic_ids_array);
                $implode_lic_remaining_amt_array = implode(',', $lic_amt_array);

                $implode_festAdv_remaining_ids_array = implode(',', $festadv_ids_array);
                $implode_festAdv_remaining_amt_array = implode(',', $festadv_amt_array);
            }


            // Festival Advance Allowance Logic Start

            // $getFestivalAllowance = EmployeeFestivalAdvance::
            //                                                 where('employee_id',$employee->id)
            //                                                 ->where('applicable_month',$month)
            //                                                 ->where('apply_status',1)->first();

            // if(!empty($getFestivalAllowance)){
            //     $totalFestivalAdvance           = $getFestivalAllowance->total_amount;
            //     $remainingtotalFestivalAdvance  = $getFestivalAllowance->total_amount;

            //     $total_allowance                = $getFestivalAllowance->total_amount;
            //     $remaining_total_allowance      = $getFestivalAllowance->total_amount;

            // }

            // Festival Advance Allowance Logic End


            // Calculate total deduction including stamp duty
            $total_deduction += $STAMP_DUTY;
            $remaining_total_deduction += $STAMP_DUTY;

            // check if status apply or not
            if ($employee?->employee_status === null || ($employee?->employee_status && $employee?->employee_status?->is_salary_applicable == 0)) {

                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date);
                if ($employee?->employee_status?->applicable_date > $from_date) {
                    if($employee?->employee_status?->applicable_date > $to_date){
                        // $present_days = $numberOfDaysInMonth;
                        $present_days = $get_present_days->total_present_days + $get_present_days->total_leave;
                    }else{
                        $present_days = $fromDate->diffInDays($employee?->employee_status?->applicable_date) + 1;
                    }

                } elseif ($employee?->employee_status && $employee?->employee_status?->applicable_date <= $from_date) {

                    $present_days = 0;
                } else {

                    // Assuming present days for now
                    // $present_days = $numberOfDaysInMonth;
                    if(empty($get_present_days))
                    {
                        $present_days = 0;

                    }else{
                        $present_days = $get_present_days->total_present_days + $get_present_days->total_leave;
                    }
                    // $present_days = $get_present_days->total_present_days;
                }

                // Calculate salary per day and salary based on present days

                $salary_per_day = $basicSalary / $numberOfDaysInMonth; //change if salary calculates on basic plus gpay
                $salary_based_on_present_day = round($salary_per_day * $present_days);

                if ($present_days != $numberOfDaysInMonth) {
                    $remaining_salary_based_on_present_day = round($salary_per_day * ($numberOfDaysInMonth - $present_days));
                } else {
                    $remaining_salary_based_on_present_day = round($salary_per_day * $present_days);
                }


                // Allowance logic
                foreach ($employee->employee_allowances as $allowance) {
                    $allowanceMaster = Allowance::find($allowance->allowance_id);

                    if ($allowance->is_active == 1 && $allowanceMaster) {
                        if ($allowance->allowance_type == 1) {
                            if ($allowanceMaster->calculation == 1) {
                                $total_allowance += $allowance->allowance_amt;

                                // Store allowance details in arrays
                                $allowance_ids_array[] = $allowance->allowance_id;
                                $allowance_amt_array[] = $allowance->allowance_amt;
                                $allowance_type_array[] = $allowance->allowance_type;

                                if ((!empty($employee?->employee_status) && $employee?->employee_status?->applicable_date > $from_date) ) {
                                }else if(($present_days <= 0 && empty($employee?->employee_status)) && $allowance->allowance_id != 12){
                                    $remaining_total_allowance += $allowance->allowance_amt;
                                    $remaining_allowance_ids_array[] = $allowance->allowance_id;
                                    $remaining_allowance_amt_array[] = $allowance->allowance_amt;
                                    $remaining_allowance_type_array[] = $allowance->allowance_type;
                                }
                                else if($allowance->allowance_id != 12 && $present_days <= 0) {

                                    $remaining_total_allowance += $allowance->allowance_amt;
                                    $remaining_allowance_ids_array[] = $allowance->allowance_id;
                                    $remaining_allowance_amt_array[] = $allowance->allowance_amt;
                                    $remaining_allowance_type_array[] = $allowance->allowance_type;

                                }


                            } else {
                                $dynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * $present_days;
                                $total_allowance += round($dynamicAllowanceAmt);

                                if($present_days <= 0)
                                {
                                    if($allowance->allowance_id != 10){
                                        // if status apply and applicable
                                        $remaining_dynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * ($numberOfDaysInMonth - $present_days);
                                        $remaining_total_allowance += round($remaining_dynamicAllowanceAmt);
                                    }
                                }else{
                                    $remaining_dynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * ($numberOfDaysInMonth - $present_days);
                                    $remaining_total_allowance += round($remaining_dynamicAllowanceAmt);
                                }

                                // Store allowance details in arrays
                                $allowance_ids_array[] = $allowance->allowance_id;
                                $allowance_amt_array[] = round($dynamicAllowanceAmt);
                                $allowance_type_array[] = $allowance->allowance_type;


                                if($present_days <= 0)
                                {
                                    if($allowance->allowance_id != 10){
                                        $remaining_allowance_ids_array[] = $allowance->allowance_id;
                                        $remaining_allowance_amt_array[] = round($remaining_dynamicAllowanceAmt);
                                        $remaining_allowance_type_array[] = $allowance->allowance_type;
                                    }
                                }else{
                                    $remaining_allowance_ids_array[] = $allowance->allowance_id;
                                    $remaining_allowance_amt_array[] = round($remaining_dynamicAllowanceAmt);
                                    $remaining_allowance_type_array[] = $allowance->allowance_type;
                                }

                            }
                        } elseif ($allowance->allowance_type == 2) {

                            if ($allowance->allowance_id == 1) {
                                $cal_amount = ($salary_based_on_present_day * $allowance->allowance_amt) / 100;
                                $cal_amount2 = ($remaining_salary_based_on_present_day * $allowance->allowance_amt) / 100;

                                $bncmc_share_da = $cal_amount;
                                $basicandDA =  $cal_amount + $salary_based_on_present_day;

                                $remaining_bncmc_share_da = $cal_amount2;
                                $remaining_basicandDA =  $cal_amount2 + $remaining_salary_based_on_present_day;

                                $total_allowance +=  round($cal_amount);
                                $remaining_total_allowance += round($cal_amount2);
                            } else {
                                $cal_amount = ($salary_based_on_present_day * $allowance->allowance_amt) / 100;
                                $cal_amount2 = ($remaining_salary_based_on_present_day * $allowance->allowance_amt) / 100;

                                $total_allowance += round($cal_amount);
                                $remaining_total_allowance += round($cal_amount2);
                            }
                            // Store allowance details in arrays
                            $allowance_ids_array[] = $allowance->allowance_id;
                            $allowance_amt_array[] = round($cal_amount);
                            $allowance_type_array[] = $allowance->allowance_type;

                            $remaining_allowance_ids_array[] = $allowance->allowance_id;
                            $remaining_allowance_amt_array[] = round($cal_amount2);
                            $remaining_allowance_type_array[] = $allowance->allowance_type;
                        }
                    }
                }


                // Implode arrays for allowance
                $implode_allowance_ids_array = implode(',', $allowance_ids_array);
                $implode_allowance_amt_array = implode(',', $allowance_amt_array);
                $implode_allowance_type_array = implode(',', $allowance_type_array);

                $implode_remaining_allowance_ids_array = implode(',', $remaining_allowance_ids_array);
                $implode_remaining_allowance_amt_array = implode(',', $remaining_allowance_amt_array);
                $implode_remaining_allowance_type_array = implode(',', $remaining_allowance_type_array);

                // Deduction logic
                foreach ($employee->employee_deductions as $deduction) {
                    $deductionMaster = Deduction::find($deduction->deduction_id);

                    if ($deduction->is_active == 1 && $deductionMaster) {
                        if ($deduction->deduction_type == 1) {
                            if ($deductionMaster->calculation == 1) {

                                if($is_employee_retire_within_3_months && $deduction->deduction_id == 3)
                                {
                                }else{

                                    $total_deduction += $deduction->deduction_amt;

                                    if ($deduction->deduction_id == 3) {
                                        $pf_contribution += $deduction->deduction_amt;
                                    }

                                    // Store deduction details in arrays
                                    $deduction_ids_array[] = $deduction->deduction_id;
                                    $deduction_amt_array[] = $deduction->deduction_amt;
                                    $deduction_type_array[] = $deduction->deduction_type;
                                }

                                if ((!empty($employee?->employee_status) && $employee?->employee_status?->applicable_date > $from_date) ) {
                                }else if ( ($present_days <= 0 && empty($employee?->employee_status)) &&
                                        (!$is_employee_retire_within_3_months || $deduction->deduction_id != 3)){
                                    $remaining_total_deduction += $deduction->deduction_amt;
                                    $remaining_deduction_ids_array[] = $deduction->deduction_id;
                                    $remaining_deduction_amt_array[] = $deduction->deduction_amt;
                                    $remaining_deduction_type_array[] = $deduction->deduction_type;
                                }
                                else if ( $present_days <= 0 && (!$is_employee_retire_within_3_months || $deduction->deduction_id != 3)) {
                                    $remaining_total_deduction += $deduction->deduction_amt;
                                    $remaining_deduction_ids_array[] = $deduction->deduction_id;
                                    $remaining_deduction_amt_array[] = $deduction->deduction_amt;
                                    $remaining_deduction_type_array[] = $deduction->deduction_type;
                                }
                            } else {

                                if($is_employee_retire_within_3_months && $deduction->deduction_id == 3)
                                {
                                }else{
                                    $dynamicDeductionAmt = ($deduction->deduction_amt / $numberOfDaysInMonth) * $present_days;
                                    $total_deduction += round($dynamicDeductionAmt);

                                    // only applicable if status and applicable date in between
                                    $remaining_dynamicDeductionAmt = ($deduction->deduction_amt / $numberOfDaysInMonth) * ($numberOfDaysInMonth - $present_days);
                                    $remaining_total_deduction += round($remaining_dynamicDeductionAmt);


                                    if ($deduction->deduction_id == 3) {
                                        $pf_contribution += round($dynamicDeductionAmt);
                                    }
                                    // Store deduction details in arrays
                                    $deduction_ids_array[] = $deduction->deduction_id;
                                    $deduction_amt_array[] = round($dynamicDeductionAmt);
                                    $deduction_type_array[] = $deduction->deduction_type;

                                    $remaining_deduction_ids_array[] = $deduction->deduction_id;
                                    $remaining_deduction_amt_array[] = round($remaining_dynamicDeductionAmt);
                                    $remaining_deduction_type_array[] = $deduction->deduction_type;
                                }
                            }
                        } elseif ($deduction->deduction_type == 2) {

                            if ($deduction->deduction_id == 4) {
                                $cal_amount = ($basicandDA * $deduction->deduction_amt) / 100;
                                $total_deduction +=  round($cal_amount);

                                $cal_amount3 = ($remaining_basicandDA * $deduction->deduction_amt) / 100;
                                $remaining_total_deduction +=  round($cal_amount3);
                            } else {
                                if($is_employee_retire_within_3_months && $deduction->deduction_id == 3)
                                {
                                }else{
                                    $cal_amount = ($salary_based_on_present_day * $deduction->deduction_amt) / 100;
                                    $total_deduction += round($cal_amount);

                                    $cal_amount3 = ($remaining_salary_based_on_present_day * $deduction->deduction_amt) / 100;
                                    $remaining_total_deduction += round($cal_amount3);

                                    if ($deduction->deduction_id == 3) {
                                        $pf_contribution += round($cal_amount);
                                    }
                                }
                            }
                            if($is_employee_retire_within_3_months && $deduction->deduction_id == 3)
                            {}else{
                                // Store deduction details in arrays
                                $deduction_ids_array[] = $deduction->deduction_id;
                                $deduction_amt_array[] = round($cal_amount);
                                $deduction_type_array[] = $deduction->deduction_type;

                                $remaining_deduction_ids_array[] = $deduction->deduction_id;
                                $remaining_deduction_amt_array[] = round($cal_amount3);
                                $remaining_deduction_type_array[] = $deduction->deduction_type;
                            }
                        }
                    }
                }

                // Implode arrays for deduction
                $implode_deduction_ids_array = implode(',', $deduction_ids_array);
                $implode_deduction_amt_array = implode(',', $deduction_amt_array);
                $implode_deduction_type_array = implode(',', $deduction_type_array);

                $implode_remaining_deduction_ids_array = implode(',', $remaining_deduction_ids_array);
                $implode_remaining_deduction_amt_array = implode(',', $remaining_deduction_amt_array);
                $implode_remaining_deduction_type_array = implode(',', $remaining_deduction_type_array);


                if ($employee->doj > '2005-11-01' && !$is_employee_retire_within_3_months) {
                    $bncmc_share = ($salary_based_on_present_day + $bncmc_share_da) * 14 / 100;
                    $share =  round($bncmc_share);
                    $employee_share_calculate = ($salary_based_on_present_day + $bncmc_share_da) * 10 / 100;
                    $employee_share = round($employee_share_calculate);


                    $remaining_bncmc_share = ($remaining_salary_based_on_present_day + $remaining_bncmc_share_da) * 14 / 100;
                    $remaining_share =  round($remaining_bncmc_share);
                    $remaining_employee_share = ($remaining_salary_based_on_present_day + $remaining_bncmc_share_da) * 10 / 100;
                    $employee_remaining_share  =  round($remaining_employee_share);

                    $total_deduction            += $employee_share;
                    $remaining_total_deduction  += $employee_remaining_share;
                }

                // DA Differance
                $fetch_employee_da_differance = EmployeeDaDifferance::where('employee_id',$employee->id)
                ->where('Emp_Code',$employee->employee_id)
                ->where('status',0)
                ->where('given_month',$month)
                ->first();

                if($fetch_employee_da_differance){

                    if($present_days != 0 && $salary_based_on_present_day != 0){

                    $da_differance = $fetch_employee_da_differance->differance;
                    $da_differance_id = $fetch_employee_da_differance->id;

                    $fetch_employee_da_differance->where('id', $fetch_employee_da_differance->id)
                    ->update([
                    'status'    => 1,
                    ]);

                    }else{

                    $da_differance = 0;
                    $da_differance_id = null;

                    $given_month = Carbon::createFromDate(null, $fetch_employee_da_differance->given_month, 1)->addMonth()->format('n');


                    $differance_in_percent = ($fetch_employee_da_differance->DA_newRate - $fetch_employee_da_differance->DA_currentRate);

                    $fetch_employee_da_differance->where('id', $fetch_employee_da_differance->id)
                    ->update([
                    'given_month'   => $given_month,
                    'no_of_month'   => $fetch_employee_da_differance->no_of_month + 1,
                    'differance'    => $fetch_employee_da_differance->differance + ($employee->salary->basic_salary * $differance_in_percent) / 100,
                    ]);

                    }
                    }else{
                    $da_differance = 0;
                    $da_differance_id = null;
                }

                $total_allowance   += $da_differance;

                // Calculate net salary
                $Net_Salary = $salary_based_on_present_day + $total_allowance - ($total_deduction);
                $Remaining_Net_Salary = $remaining_salary_based_on_present_day + $remaining_total_allowance - ($remaining_total_deduction);


                // Get supplimentary Data
                $supplimentary_data = SupplimentaryBill::where('employee_id',$employee->id)
                                                        ->where('status',0)
                                                        ->where('to_date','<=',$to_date)->first();

                if(!empty($supplimentary_data)){
                    SupplimentaryBill::where('id', $supplimentary_data->id)
                    ->update(['status' => 1]);

                    $supplimentary_ids = $supplimentary_data->id;

                    $supplimentary_status = 1;
                }else{
                    $supplimentary_status = 0;
                    $supplimentary_ids = null;
                }


                // Create FreezeAttendance record
                $data = FreezeAttendance::create([
                    'employee_id'               => $employee->id,
                    'Emp_Code'                  => $employee->employee_id,
                    'freeze_status'             => 1,
                    'attendance_UId'            => NULL, //pending
                    'ward_id'                   => $employee->ward_id,
                    'department_id'             => $employee->department_id,
                    'designation_id'            => $employee->designation_id,
                    'working_department_id'     => ($employee->working_department_id) ? $employee->working_department_id : '',
                    'clas_id'                   => $employee->clas_id,
                    'from_date'                 => $from_date,
                    'to_date'                   => $to_date,
                    'month'                     => $month,
                    'financial_year_id'         => $financial_year_id,
                    'present_day'               =>  $present_days,
                    'basic_salary'              => ($present_days != 0) ? $salary_based_on_present_day : 0,
                    'actual_basic'              =>  $basicSalary,
                    'grade_pay'                 =>  $gradePay,
                    'allowance_Id'              => ($present_days != 0 && $basicSalary != 0) ? $implode_allowance_ids_array : '',
                    'allowance_Amt'             => ($present_days != 0 && $basicSalary != 0) ? $implode_allowance_amt_array : '',
                    'allowance_Type'            => ($present_days != 0 && $basicSalary != 0) ? $implode_allowance_type_array : '',
                    // 'festival_allowance'        => ($present_days != 0 && $basicSalary != 0) ? $totalFestivalAdvance : 0,
                    // 'festival_allowance_id'     => ($present_days != 0 && $basicSalary != 0 && !empty($getFestivalAllowance)) ? $getFestivalAllowance->id : 0,

                    'festival_allowance'        => 0,
                    'festival_allowance_id'     => 0,

                    'total_allowance'           => ($present_days != 0 && $basicSalary != 0) ? $total_allowance : 0,
                    'deduction_Id'              => ($present_days != 0 && $basicSalary != 0) ? $implode_deduction_ids_array : '',
                    'deduction_Amt'             => ($present_days != 0 && $basicSalary != 0) ? $implode_deduction_amt_array : '',
                    'deduction_Type'            => ($present_days != 0 && $basicSalary != 0) ? $implode_deduction_type_array : '',
                    'total_deduction'           => ($present_days != 0 && $basicSalary != 0) ? $total_deduction : 0,
                    'stamp_duty'                => ($present_days != 0 && $basicSalary != 0) ? $STAMP_DUTY : 0,
                    'loan_deduction_id'         => ($present_days != 0 && $basicSalary != 0) ? $implode_loan_ids_array : '',
                    'loan_deduction_amt'        => ($present_days != 0 && $basicSalary != 0) ? $implode_loan_amt_array : '',
                    'loan_deduction_bank_id'    => ($present_days != 0 && $basicSalary != 0) ? $implode_loan_bank_array : '',
                    'total_loan_deduction'      => ($present_days != 0 && $basicSalary != 0) ? $total_loan_deduction : 0,
                    'lic_deduction_id'          => ($present_days != 0 && $basicSalary != 0) ? $implode_lic_ids_array : '',
                    'lic_deduction_amt'         => ($present_days != 0 && $basicSalary != 0) ? $implode_lic_amt_array : '',
                    'total_lic_deduction'       => ($present_days != 0 && $basicSalary != 0) ? $total_lic_deduction : 0,

                    'festival_deduction_id'     => ($present_days != 0 && $basicSalary != 0) ? $implode_festAdv_ids_array : '',
                    'festival_deduction_amt'    => ($present_days != 0 && $basicSalary != 0) ? $implode_festAdv_amt_array : '',
                    'total_festival_deduction'  => ($present_days != 0 && $basicSalary != 0) ? $total_festAdv_deduction : 0,

                    'net_salary'                => ($present_days != 0 && $basicSalary != 0) ? $Net_Salary : 0,
                    'emp_name'                  =>  $employee?->fname . " " . $employee?->mname . " " . $employee?->lname,
                    'pf_account_no'             => ($employee?->pf_account_no) ? $employee?->pf_account_no : 0,
                    'pay_band_scale'            => $payScales->pay_band_scale,
                    'grade_pay_scale'           => $payScales->grade_pay_name,
                    'date_of_birth'             => $employee?->dob,
                    'date_of_appointment'       => $employee?->doj,
                    'date_of_retirement'        => $employee?->retirement_date,
                    'bank_account_number'       => $employee?->account_no,
                    'phone_no'                  => $employee?->mobile_number,
                    'corporation_share_da'      => ($present_days != 0 && $basicSalary != 0) ? $share : 0,
                    'employee_share_da'         => ($present_days != 0 && $basicSalary != 0) ? $employee_share  : 0,
                    'supplimentary_status'      => $supplimentary_status,
                    'supplimentary_ids'         => $supplimentary_ids,
                    'da_differance'             => $da_differance,
                    'employee_da_differance_id' => $da_differance_id,

                ]);



                // Remaining Salary

                if ((!empty($employee?->employee_status) && $employee?->employee_status?->applicable_date < $to_date) || $present_days < $numberOfDaysInMonth) {
                    RemainingFreezeSalary::create([
                        'employee_id'               => $employee->id,
                        'freeze_attendance_id'      => $data->id,
                        'Emp_Code'                  => $employee->employee_id,
                        'from_date'                 => $from_date,
                        'to_date'                   => $to_date,
                        'month'                     => $month,
                        'present_day'               => ($employee?->employee_status?->applicable_date > $from_date || ($present_days < $numberOfDaysInMonth && empty($employee?->employee_status))) ? $numberOfDaysInMonth - $present_days : $present_days,
                        // 'basic_salary' => ($employee?->employee_status?->applicable_date > $from_date) ? $remaining_salary_based_on_present_day : $basicSalary,
                        'basic_salary'              => $remaining_salary_based_on_present_day,
                        'actual_basic'              => $basicSalary,
                        'grade_pay'                 => $gradePay,
                        'allowance_Id'              => $implode_remaining_allowance_ids_array,
                        'allowance_Amt'             => $implode_remaining_allowance_amt_array,
                        'allowance_Type'            => $implode_remaining_allowance_type_array,
                        // 'festival_allowance'        => $remainingtotalFestivalAdvance,
                        // 'festival_allowance_id'     => (!empty($getFestivalAllowance)) ? $getFestivalAllowance->id : 0,

                        'festival_allowance'        => 0,
                        'festival_allowance_id'     => 0,

                        'total_allowance'           => $remaining_total_allowance,
                        'deduction_Id'              => $implode_remaining_deduction_ids_array,
                        'deduction_Amt'             => $implode_remaining_deduction_amt_array,
                        'deduction_Type'            => $implode_remaining_deduction_type_array,
                        'total_deduction'           => $remaining_total_deduction,
                        'stamp_duty'                => $STAMP_DUTY,
                        'loan_deduction_id'         => (!empty($implode_loan_remaining_ids_array)) ? $implode_loan_remaining_ids_array : '',
                        'loan_deduction_amt'        => (!empty($implode_loan_remaining_amt_array)) ? $implode_loan_remaining_amt_array : '',
                        'loan_deduction_bank_id'    => (!empty($implode_loan_remaining_bank_array)) ? $implode_loan_remaining_bank_array : '',
                        'total_loan_deduction'      => $total_loan_remaining_deduction,

                        'lic_deduction_id'          => (!empty($implode_lic_remaining_ids_array)) ? $implode_lic_remaining_ids_array : '',
                        'lic_deduction_amt'         => (!empty($implode_lic_remaining_amt_array)) ? $implode_lic_remaining_amt_array : '',
                        'total_lic_deduction'       => $total_lic_remaining_deduction,

                        'festival_deduction_id'     => (!empty($implode_festAdv_remaining_ids_array)) ? $implode_festAdv_remaining_ids_array : '',
                        'festival_deduction_amt'    => (!empty($implode_festAdv_remaining_amt_array)) ? $implode_festAdv_remaining_amt_array : '',
                        'total_festival_deduction'  => $total_festAdv_remaining_deduction,

                        'net_salary'                => $Remaining_Net_Salary,
                        'corporation_share_da'      => $remaining_share,
                        'employee_share_da'         => $employee_remaining_share,
                    ]);
                }

                // Store PF Data
                EmployeeProvidentFund::create([
                    'employee_id'                   => $employee->id,
                    'Emp_Code'                      => $employee->employee_id,
                    'pf_account_no'                 => $employee->pf_account_no,
                    'current_month'                 => $to_date,
                    'salary_month'                  => $from_date,
                    'financial_year_id'             => $financial_year_id,
                    'pf_contribution'               => ($employee?->employee_status) ? 0 : $pf_contribution,
                    'pf_loan'                       => $pf_loan,

                ]);

                if(!empty($getFestivalAllowance)){
                    EmployeeFestivalAdvance::where('id', $getFestivalAllowance->id)
                    ->update(['apply_status' => 2]);
                }

            } elseif ($employee?->employee_status && $employee?->employee_status?->is_salary_applicable == 1) {


                $applicable_present_days = 0;
                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date);
                if ($employee?->employee_status?->applicable_date > $from_date) {

                    if($employee?->employee_status?->applicable_date > $to_date){
                        $applicable_present_days = 0;
                    }else{
                    $applicable_present_days = $fromDate->diffInDays($employee?->employee_status?->applicable_date) + 1;
                    }
                }

                // Assuming present days for now
                // $present_days = $numberOfDaysInMonth;
                $present_days = $get_present_days->total_present_days + $get_present_days->total_leave;

                $salary_percent = 0;

                if ($employee?->employee_status?->salary_percent) {
                    $salary_percent = $employee?->employee_status?->salary_percent;
                }
                // else if ($employee?->employee_status?->salary_percent == 2) {
                //     $salary_percent = 50;
                // } else if ($employee?->employee_status?->salary_percent == 3) {
                //     $salary_percent = 75;
                // } else if ($employee?->employee_status?->salary_percent == 4) {
                //     $salary_percent = 100;
                // }

                $remaining_percent = (100 - $salary_percent);

                // Calculate salary per day and salary based on present days
                // $basicSalary = optional($employee->salary)->basic_salary;
                // $gradePay = optional($employee->salary)->grade_pay;
                // $basicPlusGpay = $basicSalary + $gradePay;
                $salary_per_day = $basicSalary / $numberOfDaysInMonth; //change if salary calculates on basic plus gpay


                // if applicable date in between from date and to date // 17/04/2024
                if ($applicable_present_days != 0) {
                    $salary_based_on_present_day = round(round($salary_per_day * ($present_days - $applicable_present_days)) * $salary_percent / 100);
                    $salary_based_on_applicable_day = round($salary_per_day * $applicable_present_days);

                    $remaining_based_on_present_day = round(round($salary_per_day * ($present_days - $applicable_present_days)) * $remaining_percent / 100);
                } else {
                    $salary_based_on_present_day = round(round($salary_per_day * $present_days) * $salary_percent / 100);
                    $remaining_based_on_present_day = round(round($salary_per_day * $present_days) * $remaining_percent / 100);
                }

                // Allowance logic
                foreach ($employee->employee_allowances as $allowance) {
                    $allowanceMaster = Allowance::find($allowance->allowance_id);

                    if ($allowance->is_active == 1 && $allowanceMaster) {
                        if ($allowance->allowance_type == 1) {
                            if ($allowanceMaster->calculation == 1) {
                                $total_allowance += $allowance->allowance_amt;

                                // Store allowance details in arrays
                                $allowance_ids_array[] = $allowance->allowance_id;
                                $allowance_amt_array[] = $allowance->allowance_amt;
                                $allowance_type_array[] = $allowance->allowance_type;
                            } else {

                                if ($applicable_present_days != 0) {
                                    $dynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * $applicable_present_days;
                                    $pendingdynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * ($present_days - $applicable_present_days);
                                    $total_allowance += round($dynamicAllowanceAmt);
                                    $total_allowance += round(round($pendingdynamicAllowanceAmt)  * $salary_percent / 100);

                                    $remaining_total_allowance += round(round($pendingdynamicAllowanceAmt)  * $remaining_percent / 100);
                                } else {
                                    $dynamicAllowanceAmt = ($allowance->allowance_amt / $numberOfDaysInMonth) * ($present_days - $applicable_present_days);
                                    $total_allowance += round(round($dynamicAllowanceAmt)  * $salary_percent / 100);
                                    $remaining_total_allowance += round(round($dynamicAllowanceAmt)  * $remaining_percent / 100);
                                }

                                // Store allowance details in arrays
                                $allowance_ids_array[] = $allowance->allowance_id;

                                $new_allowance_amt = 0;
                                $new_remaining_allowance_amt = 0;
                                if ($applicable_present_days != 0) {
                                    $new_allowance_amt += round($dynamicAllowanceAmt);
                                    $new_allowance_amt += round(round($pendingdynamicAllowanceAmt)  * $salary_percent / 100);
                                    $new_remaining_allowance_amt += round(round($pendingdynamicAllowanceAmt)  * $remaining_percent / 100);
                                } else {
                                    $new_allowance_amt += round(round($dynamicAllowanceAmt)  * $salary_percent / 100);
                                    $new_remaining_allowance_amt += round(round($dynamicAllowanceAmt)  * $remaining_percent / 100);
                                }

                                $allowance_amt_array[] = $new_allowance_amt;
                                $allowance_type_array[] = $allowance->allowance_type;

                                $remaining_allowance_ids_array[] = $allowance->allowance_id;
                                $remaining_allowance_amt_array[] = $new_remaining_allowance_amt;
                                $remaining_allowance_type_array[] = $allowance->allowance_type;
                            }
                        } elseif ($allowance->allowance_type == 2) {

                            if ($allowance->allowance_id == 1) {

                                if ($applicable_present_days != 0) {
                                    $cal_amount = (($salary_based_on_present_day + $salary_based_on_applicable_day) * $allowance->allowance_amt) / 100;
                                    $cal_amount1 = ($remaining_based_on_present_day * $allowance->allowance_amt) / 100;
                                } else {
                                    $cal_amount = ($salary_based_on_present_day * $allowance->allowance_amt) / 100;
                                    $cal_amount1 = ($remaining_based_on_present_day * $allowance->allowance_amt) / 100;
                                }

                                $bncmc_share_da = $cal_amount;
                                $remaining_bncmc_share_da = $cal_amount1;
                                if ($applicable_present_days != 0) {
                                    $basicandDA =  $cal_amount + ($salary_based_on_present_day + $salary_based_on_applicable_day);
                                    $remainingbasicandDA =  $cal_amount1 + ($remaining_based_on_present_day);
                                } else {
                                    $basicandDA =  $cal_amount + $salary_based_on_present_day;
                                    $remainingbasicandDA =  $cal_amount1 + $remaining_based_on_present_day;
                                }

                                $total_allowance +=  round($cal_amount);
                                $remaining_total_allowance += round($cal_amount1);
                            } else {
                                if ($applicable_present_days != 0) {
                                    $cal_amount = (($salary_based_on_present_day + $salary_based_on_applicable_day) * $allowance->allowance_amt) / 100;
                                } else {
                                    $cal_amount = ($salary_based_on_present_day * $allowance->allowance_amt) / 100;
                                }
                                $cal_amount1 = ($remaining_based_on_present_day * $allowance->allowance_amt) / 100;

                                $total_allowance += round($cal_amount);
                                $remaining_total_allowance += round($cal_amount1);
                            }
                            // Store allowance details in arrays
                            $allowance_ids_array[] = $allowance->allowance_id;
                            $allowance_amt_array[] = round($cal_amount);
                            $allowance_type_array[] = $allowance->allowance_type;

                            $remaining_allowance_ids_array[] = $allowance->allowance_id;
                            $remaining_allowance_amt_array[] = round($cal_amount1);
                            $remaining_allowance_type_array[] = $allowance->allowance_type;
                        }
                    }
                }

                // Implode arrays for allowance
                $implode_allowance_ids_array = implode(',', $allowance_ids_array);
                $implode_allowance_amt_array = implode(',', $allowance_amt_array);
                $implode_allowance_type_array = implode(',', $allowance_type_array);

                $implode_remaining_allowance_ids_array = implode(',', $remaining_allowance_ids_array);
                $implode_remaining_allowance_amt_array = implode(',', $remaining_allowance_amt_array);
                $implode_remaining_allowance_type_array = implode(',', $remaining_allowance_type_array);


                // Deduction logic
                foreach ($employee->employee_deductions as $deduction) {
                    $deductionMaster = Deduction::find($deduction->deduction_id);

                    if ($deduction->is_active == 1 && $deductionMaster) {
                        if ($deduction->deduction_type == 1) {
                            if ($deductionMaster->calculation == 1) {

                                if($is_employee_retire_within_3_months && $deduction->deduction_id == 3)
                                {
                                }else{
                                    $total_deduction += $deduction->deduction_amt;

                                    if ($deduction->deduction_id == 3) {
                                        $pf_contribution += $deduction->deduction_amt;
                                    }

                                    // Store deduction details in arrays
                                    $deduction_ids_array[] = $deduction->deduction_id;
                                    $deduction_amt_array[] = $deduction->deduction_amt;
                                    $deduction_type_array[] = $deduction->deduction_type;
                                }
                            } else {

                                if ($applicable_present_days != 0) {
                                    $dynamicDeductionAmt = ($deduction->deduction_amt / $numberOfDaysInMonth)  * $applicable_present_days;
                                    $pendingdynamicDeductionAmt = ($deduction->deduction_amt / $numberOfDaysInMonth) * ($present_days - $applicable_present_days);
                                    $total_deduction += round($dynamicDeductionAmt);
                                    $total_deduction += round(round($pendingdynamicDeductionAmt)  * $salary_percent / 100);

                                    $remaining_total_deduction += round(round($pendingdynamicDeductionAmt)  * $remaining_percent / 100);
                                } else {
                                    $dynamicDeductionAmt = ($deduction->deduction_amt / $numberOfDaysInMonth) * ($present_days - $applicable_present_days);
                                    $total_deduction += round(round($dynamicDeductionAmt) * $salary_percent / 100);
                                    $remaining_total_deduction += round(round($dynamicDeductionAmt) * $remaining_percent / 100);
                                }

                                if ($deduction->deduction_id == 3) {
                                    $pf_contribution += round(round($dynamicDeductionAmt) * $salary_percent / 100);
                                }

                                // Store deduction details in arrays
                                $deduction_ids_array[] = $deduction->deduction_id;

                                $new_deduction_amt = 0;
                                $new_remaining_deduction_amt = 0;
                                if ($applicable_present_days != 0) {
                                    $new_deduction_amt += round($dynamicDeductionAmt);
                                    $new_deduction_amt += round(round($pendingdynamicDeductionAmt)  * $salary_percent / 100);
                                    $new_remaining_deduction_amt += round(round($pendingdynamicDeductionAmt)  * $remaining_percent / 100);
                                } else {
                                    $new_deduction_amt += round(round($dynamicDeductionAmt) * $salary_percent / 100);
                                    $new_remaining_deduction_amt += round(round($dynamicDeductionAmt) * $remaining_percent / 100);
                                }


                                $deduction_amt_array[] = $new_deduction_amt;
                                $deduction_type_array[] = $deduction->deduction_type;


                                $remaining_deduction_ids_array[] = $deduction->deduction_id;
                                $remaining_deduction_amt_array[] = $new_remaining_deduction_amt;
                                $remaining_deduction_type_array[] = $deduction->deduction_type;
                            }
                        } elseif ($deduction->deduction_type == 2) {

                            if ($deduction->deduction_id == 4) {

                                $cal_amount = ($basicandDA * $deduction->deduction_amt) / 100;
                                $cal_amount1 = ($remainingbasicandDA * $deduction->deduction_amt) / 100;
                                $total_deduction +=  round($cal_amount);
                                $remaining_total_deduction +=  round($cal_amount1);
                            } else {

                                if ($applicable_present_days != 0) {
                                    $cal_amount = (($salary_based_on_present_day + $salary_based_on_applicable_day) * $deduction->deduction_amt) / 100;
                                    $cal_amount1 = (($remaining_based_on_present_day + $salary_based_on_applicable_day) * $deduction->deduction_amt) / 100;
                                } else {
                                    $cal_amount = ($salary_based_on_present_day * $deduction->deduction_amt) / 100;
                                    $cal_amount1 = ($remaining_based_on_present_day * $deduction->deduction_amt) / 100;
                                }

                                // $cal_amount = ($salary_based_on_present_day * $deduction->deduction_amt) / 100;
                                // $cal_amount1 = ($remaining_based_on_present_day * $deduction->deduction_amt) / 100;
                                $total_deduction += round($cal_amount);
                                $remaining_total_deduction +=  round($cal_amount1);
                            }

                            // Store deduction details in arrays
                            $deduction_ids_array[] = $deduction->deduction_id;
                            $deduction_amt_array[] = round($cal_amount);
                            $deduction_type_array[] = $deduction->deduction_type;


                            $remaining_deduction_ids_array[] = $deduction->deduction_id;
                            $remaining_deduction_amt_array[] = round($cal_amount1);
                            $remaining_deduction_type_array[] = $deduction->deduction_type;
                        }
                    }
                }

                // Implode arrays for deduction
                $implode_deduction_ids_array = implode(',', $deduction_ids_array);
                $implode_deduction_amt_array = implode(',', $deduction_amt_array);
                $implode_deduction_type_array = implode(',', $deduction_type_array);

                $implode_remaining_deduction_ids_array = implode(',', $remaining_deduction_ids_array);
                $implode_remaining_deduction_amt_array = implode(',', $remaining_deduction_amt_array);
                $implode_remaining_deduction_type_array = implode(',', $remaining_deduction_type_array);

                // Calculate total deduction including stamp duty
                // $total_deduction += $STAMP_DUTY;

                if ($employee->doj > '2005-11-01' && !$is_employee_retire_within_3_months) {
                    $bncmc_share = ($salary_based_on_present_day + $bncmc_share_da) * 14 / 100;
                    $share =  round($bncmc_share);

                    $employee_share_calculate = ($salary_based_on_present_day + $bncmc_share_da) * 10 / 100;
                    $employee_share = round($employee_share_calculate);

                    $remainign_bncmc_share = ($remaining_based_on_present_day + $remaining_bncmc_share_da) * 14 / 100;
                    $remaining_share =  round($remainign_bncmc_share);

                    $remaining_employee_share  = ($remaining_based_on_present_day + $remaining_bncmc_share_da) * 10 / 100;
                    $employee_remaining_share  =  round($remaining_employee_share);

                    $total_deduction            += $employee_share;
                    $remaining_total_deduction  += $employee_remaining_share;

                }

                 // DA Differance
                 $fetch_employee_da_differance = EmployeeDaDifferance::where('employee_id',$employee->id)
                 ->where('Emp_Code',$employee->employee_id)
                 ->where('status',0)
                 ->where('given_month',$month)
                 ->first();


                if($fetch_employee_da_differance){

                    if($present_days != 0 && $salary_based_on_present_day != 0){

                        $da_differance = $fetch_employee_da_differance->differance;
                        $da_differance_id = $fetch_employee_da_differance->id;

                        $fetch_employee_da_differance->where('id', $fetch_employee_da_differance->id)
                        ->update([
                        'status'    => 1,
                        ]);

                        }else{

                            $da_differance = 0;
                            $da_differance_id = null;

                            $given_month = Carbon::createFromDate(null, $fetch_employee_da_differance->given_month, 1)->addMonth()->format('n');
                            $differance_in_percent = ($fetch_employee_da_differance->DA_newRate - $fetch_employee_da_differance->DA_currentRate);

                            $fetch_employee_da_differance->where('id', $fetch_employee_da_differance->id)
                            ->update([
                            'given_month'   => $given_month,
                            'no_of_month'   => $fetch_employee_da_differance->no_of_month + 1,
                            'differance'    => $fetch_employee_da_differance->differance + ($employee->salary->basic_salary * $differance_in_percent) / 100,
                            ]);

                        }
                }else
                {
                        $da_differance = 0;
                        $da_differance_id = null;
                }

                $total_allowance += $da_differance;

                // Calculate net salary

                if ($applicable_present_days != 0) {
                    $Net_Salary = ($salary_based_on_present_day + $salary_based_on_applicable_day) + $total_allowance - ($total_deduction);
                    $Remaining_Net_Salary = $remaining_based_on_present_day  + $remaining_total_allowance - ($remaining_total_deduction);
                } else {
                    $Net_Salary = $salary_based_on_present_day + $total_allowance - ($total_deduction);
                    $Remaining_Net_Salary = $remaining_based_on_present_day + $remaining_total_allowance - ($remaining_total_deduction);
                }


                 // Get supplimentary Data
                 $supplimentary_data = SupplimentaryBill::where('employee_id',$employee->id)
                                                        ->where('status',0)
                                                        ->where('to_date','<=',$to_date)->first();

                if(!empty($supplimentary_data)){
                    SupplimentaryBill::where('id', $supplimentary_data->id)
                    ->update(['status' => 1]);

                    $supplimentary_ids = $supplimentary_data->id;

                    $supplimentary_status = 1;
                }else{
                    $supplimentary_status = 0;
                    $supplimentary_ids = null;
                }


                // Create FreezeAttendance record
                 $data = FreezeAttendance::create([
                    'employee_id'                   => $employee->id,
                    'Emp_Code'                      => $employee->employee_id,
                    'freeze_status'                 => 1,
                    'attendance_UId'                => NULL, //pending
                    'ward_id'                       => $employee->ward_id,
                    'department_id'                 => $employee->department_id,
                    'designation_id'                => $employee->designation_id,
                    'working_department_id'         => ($employee->working_department_id) ? $employee->working_department_id : '',
                    'clas_id'                       => $employee->clas_id,
                    'from_date'                     => $from_date,
                    'to_date'                       => $to_date,
                    'month'                         => $month,
                    'financial_year_id'             => $financial_year_id,
                    'present_day'                   => $present_days,
                    'basic_salary'                  => ($applicable_present_days != 0) ? ($salary_based_on_applicable_day + $salary_based_on_present_day) : $salary_based_on_present_day,
                    'actual_basic'                  => $basicSalary,
                    'grade_pay'                     => $gradePay,
                    'allowance_Id'                  => $implode_allowance_ids_array,
                    'allowance_Amt'                 => $implode_allowance_amt_array,
                    'allowance_Type'                => $implode_allowance_type_array,
                    // 'festival_allowance'            => $totalFestivalAdvance,
                    // 'festival_allowance_id'         => (!empty($getFestivalAllowance)) ? $getFestivalAllowance->id : 0,

                    'festival_allowance'            => 0,
                    'festival_allowance_id'         => 0,

                    'total_allowance'               => $total_allowance,
                    'deduction_Id'                  => $implode_deduction_ids_array,
                    'deduction_Amt'                 => $implode_deduction_amt_array,
                    'deduction_Type'                => $implode_deduction_type_array,
                    'total_deduction'               => $total_deduction,
                    'stamp_duty'                    => $STAMP_DUTY,
                    'loan_deduction_id'             => $implode_loan_ids_array,
                    'loan_deduction_amt'            => $implode_loan_amt_array,
                    'loan_deduction_bank_id'        => $implode_loan_bank_array,
                    'total_loan_deduction'          => $total_loan_deduction,

                    'lic_deduction_id'              => $implode_lic_ids_array,
                    'lic_deduction_amt'             => $implode_lic_amt_array,
                    'total_lic_deduction'           => $total_lic_deduction,

                    'festival_deduction_id'         => $implode_festAdv_ids_array,
                    'festival_deduction_amt'        => $implode_festAdv_amt_array,
                    'total_festival_deduction'      => $total_festAdv_deduction,

                    'net_salary'                    => $Net_Salary,
                    'emp_name'                      => $employee?->fname . " " . $employee?->mname . " " . $employee?->lname,
                    'pf_account_no'                 => ($employee?->pf_account_no) ? $employee?->pf_account_no : 0,
                    'pay_band_scale'                => $payScales->pay_band_scale,
                    'grade_pay_scale'               => $payScales->grade_pay_name,
                    'date_of_birth'                 => $employee?->dob,
                    'date_of_appointment'           => $employee?->doj,
                    'date_of_retirement'            => $employee?->retirement_date,
                    'bank_account_number'           => $employee?->account_no,
                    'phone_no'                      => $employee?->mobile_number,
                    'corporation_share_da'          => $share,
                    'employee_share_da'             => $employee_share,
                    'salary_percentage'             => $salary_percent,
                    'supplimentary_status'          => $supplimentary_status,
                    'supplimentary_ids'             => $supplimentary_ids,
                    'da_differance'                 => $da_differance,
                    'employee_da_differance_id'     => $da_differance_id
                ]);


                // Remaining Salary

                RemainingFreezeSalary::create([
                    'employee_id' => $employee->id,
                    'freeze_attendance_id' => $data->id,
                    'Emp_Code' => $employee->employee_id,
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'month' => $month,
                    'present_day' => $numberOfDaysInMonth,
                    'basic_salary' => $remaining_based_on_present_day,
                    'actual_basic' => $basicSalary,
                    'grade_pay' => $gradePay,
                    'allowance_Id' => $implode_remaining_allowance_ids_array,
                    'allowance_Amt' => $implode_remaining_allowance_amt_array,
                    'allowance_Type' => $implode_remaining_allowance_type_array,
                    'total_allowance' => $remaining_total_allowance,
                    'deduction_Id' => $implode_remaining_deduction_ids_array,
                    'deduction_Amt' => $implode_remaining_deduction_amt_array,
                    'deduction_Type' => $implode_remaining_deduction_type_array,
                    'total_deduction' => $remaining_total_deduction,
                    'stamp_duty' => $STAMP_DUTY,
                    'loan_deduction_id' => (!empty($implode_loan_remaining_ids_array) && $check_status != 1) ? $implode_loan_remaining_ids_array : '',
                    'loan_deduction_amt' => (!empty($implode_loan_remaining_amt_array) && $check_status != 1) ? $implode_loan_remaining_amt_array : '',
                    'loan_deduction_bank_id' => (!empty($implode_loan_remaining_bank_array) && $check_status != 1) ? $implode_loan_remaining_bank_array : '',
                    'total_loan_deduction' => ($check_status != 1) ? $total_loan_remaining_deduction : 0,

                    'lic_deduction_id' => (!empty($implode_lic_remaining_ids_array) && $check_status != 1) ? $implode_lic_remaining_ids_array : '',
                    'lic_deduction_amt' => (!empty($implode_lic_remaining_amt_array) && $check_status != 1) ? $implode_lic_remaining_amt_array : '',
                    'total_lic_deduction' => ($check_status != 1) ? $total_lic_remaining_deduction : 0,

                    'festival_deduction_id'     => (!empty($implode_festAdv_remaining_ids_array) && $check_status != 1) ? $implode_festAdv_remaining_ids_array : '',
                    'festival_deduction_amt'    => (!empty($implode_festAdv_remaining_amt_array)&& $check_status != 1 ) ? $implode_festAdv_remaining_amt_array : '',
                    'total_festival_deduction'  => ($check_status != 1) ? $total_festAdv_remaining_deduction : 0,

                    'net_salary' => $Remaining_Net_Salary,
                    'corporation_share_da' => $remaining_share,
                    'employee_share_da'             => $employee_remaining_share,
                    'salary_percentage'     => $remaining_percent,
                ]);

                // Store PF Data
                EmployeeProvidentFund::create([
                    'employee_id'                   => $employee->id,
                    'Emp_Code'                      => $employee->employee_id,
                    'pf_account_no'                 => $employee->pf_account_no,
                    'current_month'                 => $to_date,
                    'salary_month'                  => $from_date,
                    'financial_year_id'             => $financial_year_id,
                    'pf_contribution'               => ($pf_contribution) ? $pf_contribution : 0,
                    'pf_loan'                       => $pf_loan,

                ]);

                if(!empty($getFestivalAllowance)){
                    EmployeeFestivalAdvance::where('id', $getFestivalAllowance->id)
                    ->update(['apply_status' => 2]);
                }
            }
        }
    }
}
