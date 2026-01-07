<?php

namespace App\Imports;

use App\Models\VendorsData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendorsImport implements ToCollection, WithHeadingRow
{
    public int $successCount = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            $data = [
                'name'        => trim($row['vendor_name'] ?? ''),
                'email'       => trim($row['email'] ?? ''),
                'description' => $row['description'] ?? null,
                'state'       => trim($row['state'] ?? ''),
                'project_url' => $row['project_url'] ?? null,
                'status'      => strtolower($row['status'] ?? 'active'),
                'projects'    => !empty($row['projects'])
                    ? array_map('trim', explode(',', $row['projects']))
                    : [],
                'logo'        => $row['logo'] ?? null,
            ];

            $validator = Validator::make($data, [
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:vendors,email',
                'state' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row'     => $index + 2,
                    'message' => $validator->errors()->first(),
                ];
                continue;
            }

            VendorsData::create($data);
            $this->successCount++;
        }
    }
}
