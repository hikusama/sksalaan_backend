<?php

namespace App\Http\Controllers;

use App\Models\RegistrationCycle;
use App\Models\YouthUser;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XportExcel extends Controller
{

    public function calcAge($dob)
    {
        $dob = new DateTime($dob);
        $now = new DateTime();
        $interval = $now->diff($dob);

        return $interval->y;
    }
    public function export(Request $request)
    {
        $cycleID = $request->input('cID', 'all');
        if ($cycleID !== 'all') {
            $cycle = RegistrationCycle::findOrFail($cycleID);
        }
        $type = $request->input('type');
        $start = $request->input('start');
        $end = $request->input('end');
        if ($type === 3) {
            try {
                $start = Carbon::parse($start);
                $end = Carbon::parse($end);
            } catch (\Exception  $th) {
                return response()->json(['message' => 'Invalid date'], 400);
            }
            $validator = Validator::make([
                'start' => $start,
                'end' => $end,
            ], [
                'start' => 'nullable|date|before_or_equal:today',
                'end' => 'nullable|date|after_or_equal:start',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }
        } elseif ($type == 1) {
            try {
                $start = Carbon::parse($start);
            } catch (\Exception  $th) {
                return response()->json(['message' => 'Invalid date'], 400);
            }
            $validator = Validator::make([
                'start' => $start,
            ], [
                'start' => 'nullable|date|before_or_equal:today',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }
        }


        if ($end && !$start) {
            return response()->json(['message' => 'Set the start date'], 400);
        }

        $query = YouthUser::whereNotNull('user_id')
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->with([
                'info',
                'educbg',
                'civicInvolvement'
            ]);
        $date = 'All';
        if ($type == 3) {
            if (!$start || !$end) {
                return response()->json(['message' => 'Set all the date'], 400);
            }

            if ($start > $end) {
                return response()->json(['message' => 'Start must lower than end'], 400);
            }
            $query->whereBetween('created_at', [$start, $end]);
            $date = Carbon::parse($start)->format('F j, Y') . ' to ' . Carbon::parse($end)->format('F j, Y');
        } elseif ($type == 1) {
            if (!$start) {
                return response()->json(['message' => 'Set the start date'], 400);
            }
            $query->whereDate('created_at', $start);
            $date = Carbon::parse($start)->format('F j, Y');
        }

        $users = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($date == 'All') {
            $sheet->setCellValue('A1', 'All records');
        } else {
            $sheet->setCellValue('A1', 'Record/s as of');
            $sheet->setCellValue('B1', $date);
        }
        $sheet->setCellValue('D1', 'From');
        $sheet->setCellValue('E1', ($cycleID !== 'all' ?  $cycle->cycleName : 'All') . ' Cycle');
        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Name');
        $sheet->setCellValue('C2', 'Age');
        $sheet->setCellValue('D2', 'Sex');
        $sheet->setCellValue('E2', 'Gender');
        $sheet->setCellValue('F2', 'Civil status');

        $sheet->setCellValue('G2', 'Address');
        $sheet->setCellValue('H2', 'Birth date');
        $sheet->setCellValue('I2', 'Registered at');
        $sheet->setCellValue('J2', 'Birth place');

        $sheet->setCellValue('K2', 'Youth Type');
        $sheet->setCellValue('L2', 'Skills');
        $sheet->setCellValue('M2', 'Contact#');
        $sheet->setCellValue('N2', 'No children');
        $sheet->setCellValue('O2', 'Height');
        $sheet->setCellValue('P2', 'Weight');
        $sheet->setCellValue('Q2', 'Religion');
        $sheet->setCellValue('R2', 'Occupation');

        $sheet->setCellValue('S1', 'Education');
        $sheet->setCellValue('S2', 'Level');
        $sheet->setCellValue('T2', 'School');
        $sheet->setCellValue('U2', 'Last Attendance');
        $sheet->setCellValue('V2', 'Graduated');

        $sheet->setCellValue('X1', 'Civic Involvement');
        $sheet->setCellValue('X2', 'Organization');
        $sheet->setCellValue('Y2', 'Address');
        $sheet->setCellValue('Z2', 'Date');
        $sheet->setCellValue('AA2', 'Graduated');


        $num = 1;
        $row = 3;
        foreach ($users as $user) {
            $info = $user->info;

            $sheet->setCellValueExplicit("A{$row}", strval($num), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", $info ? "{$info->lname}, {$info->fname} {$info->mname}" : 'N/A');
            $sheet->setCellValue("C{$row}", $this->calcAge($info->dateOfBirth) ?? '');
            $sheet->setCellValue("D{$row}", $info->sex ?? '');
            $sheet->setCellValue("E{$row}", $info->gender ?? 'N/A');
            $sheet->setCellValue("F{$row}", $info->civilStatus ?? '');
            $sheet->setCellValue("G{$row}", $info->address ?? '');

            $dob = $info?->dateOfBirth ? Carbon::parse($info->dateOfBirth)->format('F j, Y') : '';
            $crt = $user->created_at ? Carbon::parse($user->created_at)->format('F j, Y') : '';
            $sheet->setCellValue("H{$row}", $dob);

            $sheet->setCellValue("I{$row}", $crt);
            $sheet->setCellValue("J{$row}", $info->placeOfBirth ?? '');
            $sheet->setCellValue("K{$row}", $user->youthType ?? '');
            $sheet->setCellValue("L{$row}", $user->skills ?? '');
            $sheet->setCellValueExplicit("M{$row}", strval(($info->contactNo ?? '0')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("N{$row}", strval(($info->noOfChildren ?? '0')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("O{$row}", strval(($info->height ?? '')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("P{$row}", strval(($info->weight ?? '')), DataType::TYPE_STRING);
            $sheet->setCellValue("Q{$row}", $info->religion ?? '');
            $sheet->setCellValue("R{$row}", $info->occupation ?? '');

            $startRow = $row;
            $maxRows = 1;

            if (!empty($user->educbg)) {
                $eRow = $startRow;
                foreach ($user->educbg as $ebg) {
                    $sheet->setCellValue("S{$eRow}", $ebg->level ?? '');
                    $sheet->setCellValue("T{$eRow}", $ebg->nameOfSchool ?? '');
                    $sheet->setCellValue("U{$eRow}", $ebg->periodOfAttendance ?? '');
                    $sheet->setCellValueExplicit("V{$eRow}", strval(($ebg->yearGraduate ?? '')), DataType::TYPE_STRING);

                    $eRow++;
                }
                $maxRows = max($maxRows, $eRow - $startRow);
            }

            if (!empty($user->civicInvolvement)) {
                $cRow = $startRow;
                foreach ($user->civicInvolvement as $civic) {
                    $sheet->setCellValue("X{$cRow}", $civic->nameOfOrganization ?? '');
                    $sheet->setCellValue("Y{$cRow}", $civic->addressOfOrganization ?? '');
                    $start = $civic->start ?? '';
                    $end = $civic->end ?? '';
                    $sheet->setCellValue("Z{$cRow}", trim("{$start} - {$end}"));
                    $sheet->setCellValueExplicit("AA{$cRow}", strval(($civic->yearGraduated ?? '')), DataType::TYPE_STRING);

                    $cRow++;
                }
                $maxRows = max($maxRows, $cRow - $startRow);
            }

            $row += $maxRows;
            $num++;
        }
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }


        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, Carbon::now()->format('m-d-y_h:ia') . 'Youth.xlsx');
    }
    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res;
    }
}
