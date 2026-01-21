<?php

namespace App\Imports;

use App\Models\VendorsData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class VendorsImport implements ToCollection, WithHeadingRow
{
    /**
     * Process each row from Excel/CSV
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                /** ---------------- STATUS ---------------- */
                $status = strtolower($row['status'] ?? 'active');

                /** ---------------- DATA MAPPING ---------------- */
                $data = [
                    'name'                  => $row['name'] ?? null,
                    'email'                 => $row['email'] ?? null,
                    'description'           => $row['description'] ?? null,
                    'state'                 => $row['state'] ?? null,
                    'country'               => 'India', // default
                    'status'                => $status,
                    'projectUrl'            => $row['project_url'] ?? null,
                    'projects'              => $row['projects'] ?? null,
                    'projectsContributed'   => isset($row['projectscontributed'])
                        ? trim($row['projectscontributed'])
                        : null,
                ];

                /** ---------------- PROJECTS (JSON) ---------------- */
                if (!empty($data['projects'])) {
                    if (is_string($data['projects'])) {
                        $data['projects'] = array_map('trim', explode(',', $data['projects']));
                    }
                    $data['projects'] = json_encode($data['projects']);
                } else {
                    $data['projects'] = json_encode([]);
                }

                /** ---------------- PROJECT URL (JSON if multiple) ---------------- */
                if (!empty($data['projectUrl']) && is_string($data['projectUrl'])) {
                    if (str_contains($data['projectUrl'], ',')) {
                        $urls = array_map('trim', explode(',', $data['projectUrl']));
                        $data['projectUrl'] = json_encode($urls);
                    }
                }

                /** ---------------- DUPLICATE CHECK ---------------- */
                if (!empty($data['email']) && VendorsData::where('email', $data['email'])->exists()) {
                    Log::info('Skipping duplicate vendor', ['email' => $data['email']]);
                    continue;
                }

                /** ---------------- CREATE VENDOR ---------------- */
                $vendor = VendorsData::create($data);

                /** ---------------- LOGO HANDLING ---------------- */
                if (!empty($row['logo']) && file_exists($row['logo'])) {
                    $path = Storage::disk('public')->putFile('vendors', $row['logo']);
                    $vendor->logo = $path;
                    $vendor->save();
                }

                Log::info('Vendor imported successfully', [
                    'vendor_id' => $vendor->id,
                    'email'     => $vendor->email
                ]);
            } catch (\Exception $e) {
                Log::error('Vendor import failed', [
                    'row'   => $row->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
