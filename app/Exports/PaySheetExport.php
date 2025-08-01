<?php

namespace App\Exports;

use App\Models\FreezeAttendance;
use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\EmployeeMonthlyLoan;
use App\Models\RemainingFreezeSalary;
use App\Models\SupplimentaryBill;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaySheetExport implements FromCollection, WithHeadings
{
    protected $freezeAttendanceData;

    public function __construct(Collection $freezeAttendanceData)
    {
        $this->freezeAttendanceData = $freezeAttendanceData;
    }

    public function collection()
    {
        // Add allowance headings dynamically
        $allowances = Allowance::get();
        $deductions = Deduction::get();

        $data = [];
        $grand_total_basic_salary = 0;
        $grand_total_da_differance = 0;
        $grand_total_earn = 0;
        $allowanceTotals = array_fill_keys($allowances->pluck('id')->toArray(), 0);
        $deductionTotals = array_fill_keys($deductions->pluck('id')->toArray(), 0);

        $grand_total_pf = 0;
        $grand_total_bank_loan = 0;
        $grand_total_stamp_duty = 0;
        $grand_total_deductions = 0;
        $grand_total_net_salary = 0;
        $grand_total_corporation_share = 0;
        $grand_total_lic = 0;
        $grand_total_festival_allowance = 0;
        $grand_total_festival_deduction = 0;
        $grand_total_employee_share = 0;

        // Supplimentary
        $grand_total_supplimentary_basic_salary = 0;
        $grand_supplimentary_allowanceTotals = [];
        $grand_supplimentary_total_earn = 0;
        $grand_supplimentary_deductionTotals = [];
        $grand_supplimentary_total_stamp_duty = 0;
        $grand_supplimentary_total_deductions = 0;
        $grand_supplimentary_total_net_salary = 0;
        $grand_supplimentary_total_corporation = 0;
        $grand_supplimentary_total_lic = 0;
        $grand_supplimentary_total_employee_share = 0;


        foreach ($this->freezeAttendanceData as $key => $freeze_attendance) {
            $explode_allowance_ids = explode(',', $freeze_attendance->allowance_Id);
            $explode_allowance_amt = explode(',', $freeze_attendance->allowance_Amt);
            $explode_deduction_ids = explode(',', $freeze_attendance->deduction_Id);
            $explode_deduction_amt = explode(',', $freeze_attendance->deduction_Amt);
            $explode_loan_ids = explode(',', $freeze_attendance->loan_deduction_id);
            $explode_bank_ids = explode(',', $freeze_attendance->loan_deduction_bank_id);

            // Grand Total Calculations
            $grand_total_basic_salary += $freeze_attendance->basic_salary;
            $grand_total_da_differance += $freeze_attendance->da_differance;

            $grand_total_earn += ($freeze_attendance->basic_salary + $freeze_attendance->total_allowance);
            $grand_total_festival_allowance+= $freeze_attendance->festival_allowance;

            $supplimentaryData = '';

            // {{-- Supplimentary Calculation --}}

                if($freeze_attendance->supplimentary_status == 1){

                    $supplimentary_record = SupplimentaryBill::
                                                            where('employee_id',$freeze_attendance->employee_id)
                                                            ->where('Emp_Code',$freeze_attendance->Emp_Code)
                                                            ->where('id',$freeze_attendance->supplimentary_ids)->first();

                    $remaining_freeze_ids = explode(',', $supplimentary_record->remaining_freeze_id);

                    $supplimentaryData = RemainingFreezeSalary::whereIn('id', $remaining_freeze_ids)->get();

                }

                $supplimentary_basic_salary = 0;
                $supplimentary_total_earn = 0;

                $supplimentaryallowanceTotals = [];
                $supplimentarydeductionTotals = [];
                $supplimentaryloanTotals = [];
                $supplimentaryloanTotalsids = [];

                $supplimentary_stamp_duty = 0;
                $supplimentary_total_deductions = 0;
                $suppliemnatry_net_salary = 0;
                $suppliemnatry_corporation_share = 0;
                $suppliemnatry_present_days = 0;
                $supplimentary_lic = 0;
                $supplimentary_employee_share = 0;


                if(!empty($supplimentaryData)){
                    foreach ($supplimentaryData as $freeze){

                        $supplimentary_explode_allowance_ids = explode(',', $freeze->allowance_Id);
                        $supplimentary_explode_allowance_amt = explode(',', $freeze->allowance_Amt);

                        $supplimentary_explode_deduction_ids = explode(',', $freeze->deduction_Id);
                        $supplimentary_explode_deduction_amt = explode(',', $freeze->deduction_Amt);

                        $supplimentary_explode_loan_ids = explode(',', $freeze->loan_deduction_id);

                        $supplimentary_basic_salary += $freeze->basic_salary;

                        $supplimentary_total_earn   += ($freeze->basic_salary + $freeze->total_allowance);

                        $supplimentary_stamp_duty+= $freeze->stamp_duty;
                        $supplimentary_lic+= $freeze->total_lic_deduction;

                        $supplimentary_employee_share+= $freeze->employee_share_da;

                        $supplimentary_total_deductions+=$freeze->total_deduction;

                        $suppliemnatry_net_salary+= $freeze->net_salary;
                        $suppliemnatry_corporation_share+= $freeze->corporation_share_da;

                        if($freeze->present_day == 0){
                            $startDate = Carbon::createFromFormat('Y-m-d', $freeze->from_date);
                            $endDate = Carbon::createFromFormat('Y-m-d', $freeze->to_date);
                            $numberOfDaysInMonth = $startDate->diffInDays($endDate);
                            $numberOfDaysInMonth += 1;
                            $suppliemnatry_present_days+= $numberOfDaysInMonth;
                        }
                        $suppliemnatry_present_days += $freeze->present_day;

                         // {{-- Allowance --}}
                         foreach ($allowances->chunk(5, true) as $chunk){
                            foreach ($chunk as $allowance){

                                $index = array_search($allowance->id, $supplimentary_explode_allowance_ids);

                                if($index !== false){

                                    if (array_key_exists($allowance->id, $supplimentaryallowanceTotals)) {
                                        // If it exists, add the deduction amount to the existing total
                                        $supplimentaryallowanceTotals[$allowance->id] += $supplimentary_explode_allowance_amt[$index];
                                    } else {
                                        // If it doesn't exist, initialize the total with the deduction amount
                                        $supplimentaryallowanceTotals[$allowance->id] = $supplimentary_explode_allowance_amt[$index];
                                    }

                                    if (array_key_exists($allowance->id, $grand_supplimentary_allowanceTotals)) {
                                        // If it exists, add the deduction amount to the existing total
                                        $grand_supplimentary_allowanceTotals[$allowance->id] += $supplimentary_explode_allowance_amt[$index];
                                    } else {
                                        // If it doesn't exist, initialize the total with the deduction amount
                                        $grand_supplimentary_allowanceTotals[$allowance->id] = $supplimentary_explode_allowance_amt[$index];
                                    }
                                }
                            }
                        }

                         // {{-- Deductions --}}
                        foreach ($deductions->chunk(5, true) as $chunk){
                            foreach ($chunk as $deduction){

                                    $index = array_search($deduction->id, $supplimentary_explode_deduction_ids);

                                if($index !== false){

                                    if (array_key_exists($deduction->id, $supplimentarydeductionTotals)) {
                                        $supplimentarydeductionTotals[$deduction->id] += $supplimentary_explode_deduction_amt[$index];
                                    } else {
                                        $supplimentarydeductionTotals[$deduction->id] = $supplimentary_explode_deduction_amt[$index];
                                    }

                                    if (array_key_exists($deduction->id, $grand_supplimentary_deductionTotals)) {
                                        $grand_supplimentary_deductionTotals[$deduction->id] += $supplimentary_explode_deduction_amt[$index];
                                    } else {
                                        $grand_supplimentary_deductionTotals[$deduction->id] = $supplimentary_explode_deduction_amt[$index];
                                    }

                                }
                            }
                        }

                        // {{-- Loan --}}

                        if (!empty($freeze->loan_deduction_id) && $freeze->loan_deduction_id != '' ){

                            $loan_ids = $supplimentary_explode_loan_ids; // Assuming $supplimentary_explode_loan_ids is an array of loan IDs
                            $emp_loans = EmployeeMonthlyLoan::with('loan')->whereIn('id', $loan_ids)->get();
                            foreach ($emp_loans as $emp_loan) {
                                $loan_name = $emp_loan->loan->loan;
                                $loan_amount = $emp_loan->installment_amount;
                                $loan_auto_id = $emp_loan->id;

                                // Store loan name and amount
                                if (array_key_exists($emp_loan->loan_id, $supplimentaryloanTotals)) {
                                    $supplimentaryloanTotals[$emp_loan->loan_id] += $loan_amount;
                                } else {
                                    $supplimentaryloanTotals[$emp_loan->loan_id] = $loan_amount;
                                }

                                if (array_key_exists($loan_auto_id, $supplimentaryloanTotalsids)) {
                                    $supplimentaryloanTotalsids[$loan_auto_id] += $loan_amount;
                                } else {
                                    $supplimentaryloanTotalsids[$loan_auto_id] = $loan_amount;
                                }
                            }
                        }

                    }
                }


                // {{-- Supplimentary Calculation End --}}

                $grand_total_supplimentary_basic_salary +=  $supplimentary_basic_salary;
                $grand_supplimentary_total_earn         +=  $supplimentary_total_earn;
                $grand_supplimentary_total_stamp_duty   +=  $supplimentary_stamp_duty;
                $grand_supplimentary_total_deductions   +=  $supplimentary_total_deductions;
                $grand_supplimentary_total_net_salary   +=  $suppliemnatry_net_salary;
                $grand_supplimentary_total_corporation  +=  $suppliemnatry_corporation_share;
                $grand_supplimentary_total_lic          +=  $supplimentary_lic;
                $grand_supplimentary_total_employee_share          +=  $supplimentary_employee_share;

                $present_days = $freeze_attendance->present_day;
                if (!empty($supplimentaryData)){
                    $present_days .= '/'.$suppliemnatry_present_days;
                }

            $rowData = [
                $key + 1, // Sr No.
                $freeze_attendance->Emp_Code,
                $present_days,
                $freeze_attendance->emp_name,
                $freeze_attendance->pay_band_scale . " " . $freeze_attendance->grade_pay_scale,
                $freeze_attendance->date_of_appointment,
                optional($freeze_attendance->ward)->name,
                optional($freeze_attendance->department)->name,
                optional($freeze_attendance->designation)->name,
                $freeze_attendance->basic_salary + $supplimentary_basic_salary,
            ];


            foreach ($allowances as $allowance){
                $index = array_search($allowance->id, $explode_allowance_ids);
                if($index !== false){
                    if (array_key_exists($allowance->id, $allowanceTotals)) {
                        $allowanceTotals[$allowance->id] += $explode_allowance_amt[$index];
                    } else {
                        $allowanceTotals[$allowance->id] = $explode_allowance_amt[$index];
                    }
                    $total_amount = $explode_allowance_amt[$index];
                    if (!empty($supplimentaryData)) {
                        $supplementary_total = isset($supplimentaryallowanceTotals[$allowance->id]) ? $supplimentaryallowanceTotals[$allowance->id] : 0;
                        $total_amount += $supplementary_total;
                    }
                    $rowData[] = $total_amount;
                }elseif(!in_array($allowance->id, $explode_allowance_ids)){
                    $supplementary_total = isset($supplimentaryallowanceTotals[$allowance->id]) ? $supplimentaryallowanceTotals[$allowance->id] : 0;
                    $rowData[] = $supplementary_total;
                }
            }

            $rowData[] = ($freeze_attendance->festival_allowance);
            $rowData[] = ($freeze_attendance->da_differance);
            // Add total earning
            $rowData[] = ($freeze_attendance->basic_salary + $freeze_attendance->total_allowance) + $supplimentary_total_earn;

            foreach ($deductions as $deduction){
                    $index = array_search($deduction->id, $explode_deduction_ids);
                    if($index !== false){
                        if (array_key_exists($deduction->id, $deductionTotals)) {
                            $deductionTotals[$deduction->id] += $explode_deduction_amt[$index];
                        } else {
                            $deductionTotals[$deduction->id] = $explode_deduction_amt[$index];
                        }

                        $total_amount = $explode_deduction_amt[$index];
                        if (!empty($supplimentaryData)) {
                            $supplementary_total = isset($supplimentarydeductionTotals[$deduction->id]) ? $supplimentarydeductionTotals[$deduction->id] : 0;
                            $total_amount += $supplementary_total;
                        }
                        $rowData[] = $total_amount;
                    }elseif(!in_array($deduction->id, $explode_deduction_ids)){
                        $supplementary_total = isset($supplimentarydeductionTotals[$deduction->id]) ? $supplimentarydeductionTotals[$deduction->id] : 0;
                        $rowData[] = $supplementary_total;
                    }
            }


            // Calculate PF Loan and Bank Loan
            $pf_amt = 0;
            $bank_loan = 0;

            if(!empty($supplimentaryData) && !empty($supplimentaryloanTotals)){
                foreach($supplimentaryloanTotals as $loan_id => $total_amt){
                     $emp_loan = EmployeeMonthlyLoan::with('loan')->where('loan_id', $loan_id)->first();
                    if(!in_array($emp_loan->loan_id, $explode_bank_ids)){
                        if ($emp_loan && $emp_loan->loan_id == 1) {
                            $pf_amt = $total_amt;
                            $grand_total_pf += $pf_amt;
                        }else{
                            $bank_loan = $total_amt;
                        }
                    }
                }
            }

            if ($freeze_attendance->loan_deduction_id){
                foreach ($explode_loan_ids as $loan_id){
                    $emp_loan = EmployeeMonthlyLoan::with('loan')->where('id', $loan_id)->first();
                    if ($emp_loan && $emp_loan->loan_id == 1) {
                        if (!empty($supplimentaryData) && isset($supplimentaryloanTotals[$emp_loan->loan_id])) {
                            $pf_amt = $supplimentaryloanTotals[$emp_loan->loan_id] + $emp_loan->installment_amount;;
                            $grand_total_pf += $pf_amt;
                        } else {
                            $pf_amt = $emp_loan->installment_amount;
                            $grand_total_pf += $pf_amt;
                        }
                    }else{
                        $bank_loan = $emp_loan->installment_amount;
                    }

                    if (!empty($supplimentaryData)) {
                        if (isset($supplimentaryloanTotals[$emp_loan->loan_id]) && $emp_loan->loan_id != 1) {
                            $bank_loan = ($emp_loan->installment_amount) + $supplimentaryloanTotals[$emp_loan->loan_id];
                        }
                    } else if($emp_loan->loan_id != 1) {
                        $bank_loan = $emp_loan->installment_amount;
                    }
                }
            }

            $grand_total_bank_loan += $bank_loan;
            $grand_total_stamp_duty += $freeze_attendance->stamp_duty;
            $grand_total_lic+=$freeze_attendance->total_lic_deduction;

            $grand_total_deductions += $freeze_attendance->total_deduction;

            $grand_total_net_salary += $freeze_attendance->net_salary;
            $grand_total_corporation_share += $freeze_attendance->corporation_share_da;
            $grand_total_festival_deduction+=$freeze_attendance->total_festival_deduction;
            $grand_total_employee_share+=$freeze_attendance->employee_share_da;

            $rowData[] = $pf_amt;
            $rowData[] = $bank_loan;
            $rowData[] = $freeze_attendance->total_lic_deduction + $supplimentary_lic;
            $rowData[] = $freeze_attendance->total_festival_deduction;
            $rowData[] = $freeze_attendance->stamp_duty + $supplimentary_stamp_duty;
            $rowData[] = $freeze_attendance->employee_share_da + $supplimentary_employee_share;
            $rowData[] = $freeze_attendance->total_deduction + $supplimentary_total_deductions;
            $rowData[] = $freeze_attendance->net_salary + $suppliemnatry_net_salary;
            $rowData[] = $freeze_attendance->corporation_share_da + $suppliemnatry_corporation_share;
            $rowData[] = optional($freeze_attendance->employee->employee_status)->remark ?? $freeze_attendance->remark;

            $data[] = $rowData;
        }

        // Add grand total row with allowance totals
        $grandTotalRow = ['Grand Total:', '', '', '', '', '', '','','', $grand_total_basic_salary + $grand_total_supplimentary_basic_salary];
        foreach ($allowances as $allowance) {

            if(isset($grand_supplimentary_allowanceTotals[$allowance->id]) && isset($allowanceTotals[$allowance->id])){
                $grandTotalRow[] = $allowanceTotals[$allowance->id] + $grand_supplimentary_allowanceTotals[$allowance->id];
            }elseif(isset($allowanceTotals[$allowance->id])){
                $grandTotalRow[] = $allowanceTotals[$allowance->id];
            }elseif(isset($grand_supplimentary_allowanceTotals[$allowance->id])){
                $grandTotalRow[] = $grand_supplimentary_allowanceTotals[$allowance->id];
            }
        }

        $grandTotalRow[] = $grand_total_festival_allowance;
        $grandTotalRow[] = $grand_total_da_differance;
        $grandTotalRow[] = $grand_total_earn + $grand_supplimentary_total_earn;

        foreach ($deductions as $deduction) {
            if(isset($grand_supplimentary_deductionTotals[$deduction->id]) && isset($deductionTotals[$deduction->id])){
                $grandTotalRow[] = $deductionTotals[$deduction->id] + $grand_supplimentary_deductionTotals[$deduction->id];
            }elseif(isset($deductionTotals[$deduction->id])){
                $grandTotalRow[] = $deductionTotals[$deduction->id];
            }elseif(isset($grand_supplimentary_deductionTotals[$deduction->id])){
                $grandTotalRow[] = $grand_supplimentary_deductionTotals[$deduction->id];
            }
        }

        $grandTotalRow[] = $grand_total_pf;
        $grandTotalRow[] = $grand_total_bank_loan;
        $grandTotalRow[] = $grand_total_lic + $grand_supplimentary_total_lic;
        $grandTotalRow[] = $grand_total_festival_deduction;
        $grandTotalRow[] = $grand_total_stamp_duty + $grand_supplimentary_total_stamp_duty;
        $grandTotalRow[] = $grand_total_employee_share + $grand_supplimentary_total_employee_share;
        $grandTotalRow[] = $grand_total_deductions + $grand_supplimentary_total_deductions;
        $grandTotalRow[] = $grand_total_net_salary + $grand_supplimentary_total_net_salary;
        $grandTotalRow[] = $grand_total_corporation_share + $grand_supplimentary_total_corporation;


        $data[] = $grandTotalRow;

        return collect($data);
    }



    public function headings(): array
    {
        $headings = [
            'Sr No.',
            'Employee Code',
            'Total Present Days',
            'Name',
            'Pay Scale',
            'Date of Appointment',
            'Ward',
            'Department',
            'Designation',
            'Basic + GP',
        ];

        // Add allowance headings dynamically
        $allowances = Allowance::get();
        foreach ($allowances->chunk(5) as $chunk) {
            foreach ($chunk as $allowance) {
                $headings[] = substr($allowance->allowance, 0, 5);
            }
        }
        $headings[] = 'Fest Adv.';
        $headings[] = 'DA Differance';
        $headings[] = 'Total_Earn';

        $deductions = Deduction::get();
        foreach ($deductions->chunk(5) as $chunk) {
            foreach ($chunk as $deduction) {
                $headings[] = substr($deduction->deduction, 0, 5);
            }
        }

        $headings[] = 'PF Loan';
        $headings[] = 'Bank Loan';
        $headings[] = 'LIC';
        $headings[] = 'Festival';
        $headings[] = 'Stamp Duty';
        $headings[] = 'Employee Share';
        $headings[] = 'Total Deduct';

        $headings[] = 'Net Salary';
        $headings[] = 'Corporation Share';
        $headings[] = 'Remark';

        return $headings;
    }
}
