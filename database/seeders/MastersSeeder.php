<?php

namespace Database\Seeders;

use App\Models\Allowance;
use App\Models\Ward;
use App\Models\Department;
use App\Models\Bank;
use App\Models\Clas;
use App\Models\Corporation;
use App\Models\Deduction;
use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\FinancialYear;
use App\Models\Loan;
use App\Models\PayMst;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MastersSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Wards Seeder
        $wards = [
            [
                'id' => 1,
                'name' => 'Ward 1',
                'initial' => 'w1',
            ],
            [
                'id' => 2,
                'name' => 'Ward 2',
                'initial' => 'w2',
            ]
        ];

        foreach ($wards as $ward) {
            Ward::updateOrCreate([
                'id' => $ward['id']
            ], [
                'id' => $ward['id'],
                'name' => $ward['name'],
                'initial' => $ward['initial']
            ]);
        }

        // Department Seeder

        $depts = [
            [
                'id' => 1,
                'ward_id' => 1,
                'name' => 'Department 1',
                'initial' => 'dept1',
            ],
            [
                'id' => 2,
                'ward_id' => 2,
                'name' => 'Department 2',
                'initial' => 'dept2',
            ]
        ];

        foreach ($depts as $dept) {
            Department::updateOrCreate([
                'id' => $dept['id']
            ], [
                'id'        => $dept['id'],
                'ward_id'   => $dept['ward_id'],
                'name'      => $dept['name'],
                'initial'   => $dept['initial']
            ]);
        }

        // Class Seeder

        $class = [
            [
                'id' => 1,
                'name' => 'Class 1',
                'working_year' => '58',
            ],
            [
                'id' => 2,
                'name' => 'Class 2',
                'working_year' => '58',
            ]
        ];

        foreach ($class as $clas) {
            Clas::updateOrCreate([
                'id' => $clas['id']
            ], [
                'id'             => $clas['id'],
                'name'           => $clas['name'],
                'working_year'   => $clas['working_year']
            ]);
        }

        // Bank Seeder

        $banks = [
            [
                'id' => 1,
                'name' => 'State Bank of India',
                'initial' => 'SBI',
            ],
            [
                'id' => 2,
                'name' => 'Bank of Baroda',
                'initial' => 'BoB',
            ]
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate([
                'id' => $bank['id']
            ], [
                'id' => $bank['id'],
                'name' => $bank['name'],
                'initial' => $bank['initial']
            ]);
        }

        // Designation Seeder

        $designations = [
            [
                'id'        => 1,
                'ward_id'   => 1,
                'department_id' => 1,
                'clas_id'       => 1,
                'name'      => 'Cleark',
            ],
            [
                'id'        => 2,
                'ward_id'   => 2,
                'department_id' => 2,
                'clas_id'       => 2,
                'name'      => 'HOD',
            ]
        ];

        foreach ($designations as $designation) {
            Designation::updateOrCreate([
                'id' => $designation['id']
            ], [
                'ward_id' => $designation['ward_id'],
                'department_id' => $designation['department_id'],
                'clas_id' => $designation['clas_id'],
                'name' => $designation['name']
            ]);
        }

        // Leave Type Seeder

        $leaveTypes = [
            [
                'id'            => 1,
                'name'          => 'Earned Leave',
                'type'          => 1,
                'no_of_leaves'  => 12,
                'carry_forward' => 1,
                'encashable'    => 1,
            ],
            [
                'id'            => 2,
                'name'          => 'Casual Leave',
                'type'          => 1,
                'no_of_leaves'  => 12,
                'carry_forward' => 1,
                'encashable'    => 2,
            ]
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate([
                'id' => $leaveType['id']
            ], [
                'name' => $leaveType['name'],
                'type' => $leaveType['type'],
                'no_of_leaves' => $leaveType['no_of_leaves'],
                'carry_forward' => $leaveType['carry_forward'],
                'encashable' => $leaveType['encashable']
            ]);
        }

        // Financial Year
        $financialYear = [
            [
                'id'            => 1,
                'from_date'     => '2024-04-01',
                'to_date'       => '2025-03-31',
                'title'         => 'FY2024-2025',
                'is_active'     => 1,
            ],
            [
                'id'            => 2,
                'from_date'     => '2023-04-01',
                'to_date'       => '2024-03-31',
                'title'         => 'FY2023-2024',
                'is_active'     => 0,
            ]
        ];

        foreach ($financialYear as $financialyear) {
            FinancialYear::updateOrCreate([
                'id' => $financialyear['id']
            ], [
                'from_date' => $financialyear['from_date'],
                'to_date' => $financialyear['to_date'],
                'title' => $financialyear['title'],
                'is_active' => $financialyear['is_active']
            ]);
        }

        // Pay Mst
        $payMst = [
            [
                'id'            => 1,
                'name'     => '7th Pay',
            ]
        ];

        foreach ($payMst as $payMst) {
            PayMst::updateOrCreate([
                'id' => $payMst['id']
            ], [
                'name' => $payMst['name'],
            ]);
        }

        // Allowance Master
        $allowanceMst = [
            [
                'id'            => 1,
                'allowance'     => 'DEARNESS ALLOWANCE',
                'type'          => 2,
                'amount'        => 46,
                'is_applicable' => 0,
                'calculation'   => 2,
                'allowance_in_marathi'=> 'DEARNESS ALLOWANCE'
            ],
            [
                'id'            => 2,
                'allowance'     => 'HRA',
                'type'          => 2,
                'amount'        => 18,
                'is_applicable' => 0,
                'calculation'   => 2,
                'allowance_in_marathi'=> 'HRA'
            ],
            [
                'id'            => 3,
                'allowance'     => 'MEDICAL ALLOWANCE',
                'type'          => 1,
                'amount'        => 1000,
                'is_applicable' => 0,
                'calculation'   => 1,
                'allowance_in_marathi'=> 'MEDICAL ALLOWANCE'
            ],
            [
                'id'            => 4,
                'allowance'     => 'CITY ALLOWANCE',
                'type'          => 1,
                'amount'        => 120,
                'is_applicable' => 0,
                'calculation'   => 2,
                'allowance_in_marathi'=> 'CITY ALLOWANCE'
            ],
            [
                'id'            => 5,
                'allowance'     => 'VEHICLE ALLOWANCE',
                'type'          => 1,
                'amount'        => 1350,
                'is_applicable' => 0,
                'calculation'   => 1,
                'allowance_in_marathi'=> 'VEHICLE ALLOWANCE'
            ]
        ];

        foreach ($allowanceMst as $allowanceMst) {
            Allowance::updateOrCreate([
                'id' => $allowanceMst['id']
            ], [
                'allowance' => $allowanceMst['allowance'],
                'type' => $allowanceMst['type'],
                'amount' => $allowanceMst['amount'],
                'is_applicable' => $allowanceMst['is_applicable'],
                'calculation' => $allowanceMst['calculation'],
                'allowance_in_marathi' => $allowanceMst['allowance_in_marathi'],
            ]);
        }

        // Deduction Master
        $deductionMst = [
            [
                'id'            => 1,
                'deduction'     => 'PROFESSIONAL TAX',
                'type'          => 1,
                'amount'        => 200,
                'is_applicable' => 0,
                'calculation'   => 1,
                'deduction_in_marathi'=> 'PROFESSIONAL TAX'
            ],
            [
                'id'            => 2,
                'deduction'     => 'INCOME TAX',
                'type'          => 1,
                'amount'        => 0,
                'is_applicable' => 0,
                'calculation'   => 1,
                'deduction_in_marathi'=> 'INCOME TAX'
            ],
            [
                'id'            => 3,
                'deduction'     => 'PF CONTRIBUTION',
                'type'          => 1,
                'amount'        => 1000,
                'is_applicable' => 0,
                'calculation'   => 1,
                'deduction_in_marathi'=> 'PF CONTRIBUTION'
            ],
            [
                'id'            => 4,
                'deduction'     => 'PENSION CONTRIBUTION',
                'type'          => 2,
                'amount'        => 10,
                'is_applicable' => 0,
                'calculation'   => 2,
                'deduction_in_marathi'=> 'PENSION CONTRIBUTION'
            ]
        ];

        foreach ($deductionMst as $deductionMst) {
            Deduction::updateOrCreate([
                'id' => $deductionMst['id']
            ], [
                'deduction' => $deductionMst['deduction'],
                'type' => $deductionMst['type'],
                'amount' => $deductionMst['amount'],
                'is_applicable' => $deductionMst['is_applicable'],
                'calculation' => $deductionMst['calculation'],
                'deduction_in_marathi' => $deductionMst['deduction_in_marathi'],
            ]);
        }

        // Loan Seeder

        $loanMst = [
            [
                'id'            => 1,
                'loan'          => 'PF Loan',
                'initial'       => 'PF Loan',
                'loan_in_marathi'=> 'पीएफ कर्ज',
                'activity_status' => 1,
            ]
        ];

        foreach ($loanMst as $loanMst) {
            Loan::updateOrCreate([
                'id' => $loanMst['id']
            ], [
                'loan' => $loanMst['loan'],
                'initial' => $loanMst['initial'],
                'activity_status' => $loanMst['activity_status'],
                'loan_in_marathi' => $loanMst['loan_in_marathi'],
            ]);
        }

        // Corporation Master

        $CorpoMst = [
            [
                'id'            => 1,
                'name'          => 'Mira Bhayandar Municipal Corporation',
                'initial'       => 'KDMC',
                'logo'          => 'admin/images/login-logo.png'
            ]
        ];

        foreach ($CorpoMst as $corp) {
            Corporation::updateOrCreate([
                'id' => $corp['id']
            ], [
                'name' => $corp['name'],
                'initial' => $corp['initial'],
                'logo' => $corp['logo'],
            ]);
        }
    }
}
