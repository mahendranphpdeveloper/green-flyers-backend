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
                // Map fields from Excel to DB columns
                $status = $row['status'] ?? 'active';
                // Force status to lowercase
                $status = strtolower($status);

                $data = [
                    'name'        => $row['name'] ?? null,
                    'email'       => $row['email'] ?? null,
                    'description' => $row['description'] ?? null,
                    'state'       => $row['state'] ?? null,
                    // 'country' column should be set to 'India' by default since it's not present in the frontend excel/csv
                    'country'     => 'India',
                    'status'      => $status,
                    'projectUrl'  => $row['project_url'] ?? null, // Can be a string or comma-separated
                    'projects'    => $row['projects'] ?? null,
                    'projectsContributed'    => $row['projectsContributed'] ?? null,
                ];

                // Convert projects column to JSON if array/string
                if (!empty($data['projects'])) {
                    if (is_string($data['projects'])) {
                        // If multiple projects in Excel separated by commas
                        $data['projects'] = array_map('trim', explode(',', $data['projects']));
                    }
                    $data['projects'] = json_encode($data['projects']);
                } else {
                    $data['projects'] = json_encode([]);
                }

                // Convert projectUrl if multiple URLs separated by commas
                if (!empty($data['projectUrl'])) {
                    if (is_string($data['projectUrl']) && str_contains($data['projectUrl'], ',')) {
                        $urls = array_map('trim', explode(',', $data['projectUrl']));
                        $data['projectUrl'] = json_encode($urls);
                    }
                }

                // Check for existing vendor by email to avoid duplicates
                if (!empty($data['email']) && VendorsData::where('email', $data['email'])->exists()) {
                    Log::info('Skipping duplicate vendor', ['email' => $data['email']]);
                    continue;
                }

                // Create vendor
                $vendor = VendorsData::create($data);

                // If logo file path is provided in Excel, move it to storage
                if (!empty($row['logo']) && file_exists($row['logo'])) {
                    $path = Storage::disk('public')->putFile('vendors', $row['logo']);
                    $vendor->logo = $path;
                    $vendor->save();
                }

                Log::info('Vendor imported', ['vendor_id' => $vendor->id, 'email' => $vendor->email ?? null]);
            } catch (\Exception $e) {
                Log::error('Vendor import failed', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
