<?php

namespace App\Http\Controllers;

use App\Models\YouthUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XportExcel extends Controller
{
    public function export(Request $request)
    {
        $type = $request->input('type');
        $start = $request->input('start');
        $end = $request->input('end');
        if ($type != 2) {
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
                'end' => 'nullable|date|before_or_equal:today',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }
        }


        if ($end && !$start) {
            return response()->json(['message' => 'Set the start date'], 400);
        }

        $query = YouthUser::whereNotNull('user_id')->with([
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
            $sheet->setCellValue('A1', 'Record as of');
            $sheet->setCellValue('B1', $date);
        }
        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Name');
        $sheet->setCellValue('C2', 'Age');
        $sheet->setCellValue('D2', 'Sex');
        $sheet->setCellValue('E2', 'Gender');
        $sheet->setCellValue('F2', 'Civil status');

        $sheet->setCellValue('G2', 'Address');
        $sheet->setCellValue('H2', 'Birth date');
        $sheet->setCellValue('I2', 'Birth place');

        $sheet->setCellValue('J2', 'Youth Type');
        $sheet->setCellValue('K2', 'Skills');
        $sheet->setCellValue('L2', 'Contact#');
        $sheet->setCellValue('M2', 'No children');
        $sheet->setCellValue('N2', 'Height');
        $sheet->setCellValue('O2', 'Weight');
        $sheet->setCellValue('P2', 'Religion');
        $sheet->setCellValue('Q2', 'Occupation');

        $sheet->setCellValue('R1', 'Education');
        $sheet->setCellValue('R2', 'Level');
        $sheet->setCellValue('S2', 'School');
        $sheet->setCellValue('T2', 'Last Attendance');
        $sheet->setCellValue('U2', 'Graduated');

        $sheet->setCellValue('W1', 'Civic Involvement');
        $sheet->setCellValue('W2', 'Organization');
        $sheet->setCellValue('X2', 'Address');
        $sheet->setCellValue('Y2', 'Date');
        $sheet->setCellValue('Z2', 'Graduated');


        $num = 1;
        $row = 3;
        foreach ($users as $user) {
            $info = $user->info;

            $sheet->setCellValueExplicit("A{$row}", strval($num), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", $info ? "{$info->lname}, {$info->fname} {$info->mname}" : 'N/A');
            $sheet->setCellValue("C{$row}", $info->age ?? '');
            $sheet->setCellValue("D{$row}", $info->sex ?? '');
            $sheet->setCellValue("E{$row}", $info->gender ?? 'N/A');
            $sheet->setCellValue("F{$row}", $info->civilStatus ?? '');
            $sheet->setCellValue("G{$row}", $info->address ?? '');

            $dob = $info?->dateOfBirth ? Carbon::parse($info->dateOfBirth)->format('F j, Y') : '';
            $sheet->setCellValue("H{$row}", $dob);

            $sheet->setCellValue("I{$row}", $info->placeOfBirth ?? '');
            $sheet->setCellValue("J{$row}", $user->youthType ?? '');
            $sheet->setCellValue("K{$row}", $user->skills ?? '');
            $sheet->setCellValueExplicit("L{$row}", strval(($info->contactNo ?? '0')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("M{$row}", strval(($info->noOfChildren ?? '0')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("N{$row}", strval(($info->height ?? '')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("O{$row}", strval(($info->weight ?? '')), DataType::TYPE_STRING);
            $sheet->setCellValue("P{$row}", $info->religion ?? '');
            $sheet->setCellValue("Q{$row}", $info->occupation ?? '');

            $startRow = $row;
            $maxRows = 1;

            if (!empty($user->educbg)) {
                $eRow = $startRow;
                foreach ($user->educbg as $ebg) {
                    $sheet->setCellValue("R{$eRow}", $ebg->level ?? '');
                    $sheet->setCellValue("S{$eRow}", $ebg->nameOfSchool ?? '');
                    $sheet->setCellValue("T{$eRow}", $ebg->periodOfAttendance ?? '');
                    $sheet->setCellValueExplicit("U{$eRow}", strval(($ebg->yearGraduate ?? '')), DataType::TYPE_STRING);

                    $eRow++;
                }
                $maxRows = max($maxRows, $eRow - $startRow);
            }

            if (!empty($user->civicInvolvement)) {
                $cRow = $startRow;
                foreach ($user->civicInvolvement as $civic) {
                    $sheet->setCellValue("W{$cRow}", $civic->nameOfOrganization ?? '');
                    $sheet->setCellValue("X{$cRow}", $civic->addressOfOrganization ?? '');
                    $start = $civic->start ?? '';
                    $end = $civic->end ?? '';
                    $sheet->setCellValue("Y{$cRow}", trim("{$start} - {$end}"));
                    $sheet->setCellValueExplicit("Z{$cRow}", strval(($civic->yearGraduated ?? '')), DataType::TYPE_STRING);

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
}
