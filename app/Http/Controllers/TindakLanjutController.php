<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TindakLanjutController extends Controller
{
    /**
     * Get tindak lanjut hasil pengawasan data from external API
     */
    public function getHasilPengawasan(Request $request)
    {
        try {
            // Use filter parameters as API parameters if provided, otherwise use defaults
            $unitRendalKode = $request->get('unit_rendal_kode', 'D401,D402,D403,D404');
            
            // If specific unit filter is provided, use only that unit
            $filterUnitPenerbit = $request->get('filter_unit_penerbit');
            if ($filterUnitPenerbit) {
                $unitPenerbitKode = $filterUnitPenerbit;
            } else {
                $unitPenerbitKode = $request->get('unit_penerbit_kode', 'PW01,PW24');
            }
            
            // If specific year filter is provided, use only that year
            $filterTahun = $request->get('filter_tahun');
            if ($filterTahun) {
                $tahunPkpt = $filterTahun;
            } else {
                $tahunPkpt = $request->get('tahun_pkpt', '2025');
            }

            // API endpoint
            $apiUrl = 'https://api-stara.bpkp.go.id/api/tindak-lanjut/hasil-pengawasan';
            
            // Bearer token (you might want to store this in config or env)
            $bearerToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1dWlkIjoiOTI5Mzk2ZDUtNDg3Mi00MDg4LWJhNTEtZmFlMTNlNzk5NWViIiwibmFtYV9hcGxpa2FzaSI6IkRhc2hib2FyZCBQQUVQIEQzIiwidXNlcm5hbWUiOiJkYXNoYm9hcmRwYWVwZDMiLCJpYXQiOjE3NDE5MTk5NzQsImlzcyI6IiMjJC40cDFSM2YzcjNuNWkuJCMjIn0.KOJWLKnD1Hz0y7g50YKCcFutqvjgKHlfqjj5-OG6078';

            // Make API request with increased timeout
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
            ])->timeout(120) // 2 minutes timeout
            ->get($apiUrl, [
                'unit_rendal_kode' => $unitRendalKode,
                'unit_penerbit_kode' => $unitPenerbitKode,
                'tahun_pkpt' => $tahunPkpt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Apply client-side filtering only for status_tl since it's not an API parameter
                $filteredData = $data;
                $filterStatus = $request->get('filter_status');
                
                if ($filterStatus && isset($data['data']) && is_array($data['data'])) {
                    $originalData = $data['data'];
                    $filtered = array_filter($originalData, function($item) use ($filterStatus) {
                        return isset($item['status_tl']) && $item['status_tl'] === $filterStatus;
                    });
                    
                    $filteredData['data'] = array_values($filtered);
                    $filteredData['total'] = count($filtered);
                    $filteredData['original_total'] = count($originalData);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $filteredData,
                    'message' => 'Data tindak lanjut berhasil diambil'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API eksternal',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tindak lanjut data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics for monitoring dashboard
     */
    public function getSummaryStats(Request $request)
    {
        try {
            // Get the main data first
            $dataResponse = $this->getHasilPengawasan($request);
            $responseData = json_decode($dataResponse->getContent(), true);

            if (!$responseData['success']) {
                return $dataResponse;
            }

            $data = $responseData['data']['data'] ?? [];

            // Calculate summary statistics
            $totalRecords = count($data);
            $statusCounts = [];
            $unitCounts = [];

            foreach ($data as $item) {
                // Count by status
                $status = $item['status_penugasan'] ?? 'Unknown';
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

                // Count by unit
                $unit = $item['unit_penerbit_nama'] ?? 'Unknown';
                $unitCounts[$unit] = ($unitCounts[$unit] ?? 0) + 1;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_records' => $totalRecords,
                    'status_counts' => $statusCounts,
                    'unit_counts' => $unitCounts,
                    'raw_data' => $data
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating summary stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available filter options (unique values for dropdowns)
     */
    public function getFilterOptions(Request $request)
    {
        try {
            // Get all data first (without filters)
            $tempRequest = new Request();
            $dataResponse = $this->getHasilPengawasan($tempRequest);
            $responseData = json_decode($dataResponse->getContent(), true);

            if (!$responseData['success']) {
                return $dataResponse;
            }

            $data = $responseData['data']['data'] ?? [];

            // Extract unique values for filters
            $unitPenerbitOptions = [];
            $statusOptions = [];
            $tahunOptions = [];

            foreach ($data as $item) {
                // Unit Penerbit options
                if (isset($item['unit_penerbit_kode']) && isset($item['unit_penerbit'])) {
                    $key = $item['unit_penerbit_kode'];
                    if (!isset($unitPenerbitOptions[$key])) {
                        $unitPenerbitOptions[$key] = [
                            'kode' => $item['unit_penerbit_kode'],
                            'nama' => $item['unit_penerbit']
                        ];
                    }
                }

                // Status options
                if (isset($item['status_tl']) && !in_array($item['status_tl'], $statusOptions)) {
                    $statusOptions[] = $item['status_tl'];
                }

                // Tahun options
                if (isset($item['tahun_pkpt']) && !in_array($item['tahun_pkpt'], $tahunOptions)) {
                    $tahunOptions[] = $item['tahun_pkpt'];
                }
            }

            // Sort the options
            ksort($unitPenerbitOptions);
            sort($statusOptions);
            rsort($tahunOptions); // Reverse sort for years (newest first)

            return response()->json([
                'success' => true,
                'data' => [
                    'unit_penerbit' => array_values($unitPenerbitOptions),
                    'status' => array_filter($statusOptions),
                    'tahun' => array_filter($tahunOptions)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get surat tugas penugasan pengawasan data from external API
     */
    public function getSuratTugasPenugasan(Request $request)
    {
        try {
            // Use filter parameters as API parameters if provided, otherwise use defaults
            $kodePjKap = $request->get('kode_pj_kap', 'D401,D402,D403,D404');
            
            // If specific unit filter is provided, use only that unit
            $filterUnitKontributor = $request->get('filter_unit_kontributor');
            if ($filterUnitKontributor) {
                $kodeUnitKontributor = $filterUnitKontributor;
            } else {
                $kodeUnitKontributor = $request->get('kode_unit_kontributor', 'PW24');
            }
            
            // If specific year filter is provided, use only that year
            $filterTahun = $request->get('filter_tahun');
            if ($filterTahun) {
                $tahunPkpt = $filterTahun;
            } else {
                $tahunPkpt = $request->get('tahun_pkpt', '2025');
            }

            // API endpoint
            $apiUrl = 'https://api-stara.bpkp.go.id/api/surat-tugas/penugasan/pengawasan';
            
            // Bearer token (you might want to store this in config or env)
            $bearerToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1dWlkIjoiOTI5Mzk2ZDUtNDg3Mi00MDg4LWJhNTEtZmFlMTNlNzk5NWViIiwibmFtYV9hcGxpa2FzaSI6IkRhc2hib2FyZCBQQUVQIEQzIiwidXNlcm5hbWUiOiJkYXNoYm9hcmRwYWVwZDMiLCJpYXQiOjE3NDE5MTk5NzQsImlzcyI6IiMjJC40cDFSM2YzcjNuNWkuJCMjIn0.KOJWLKnD1Hz0y7g50YKCcFutqvjgKHlfqjj5-OG6078';

            // Make API request with increased timeout
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
            ])->timeout(120) // 2 minutes timeout
            ->get($apiUrl, [
                'kode_pj_kap' => $kodePjKap,
                'kode_unit_kontributor' => $kodeUnitKontributor,
                'tahun_pkpt' => $tahunPkpt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Apply client-side filtering for status if needed
                $filteredData = $data;
                $filterStatus = $request->get('filter_status');
                
                if ($filterStatus && isset($data['data']) && is_array($data['data'])) {
                    $originalData = $data['data'];
                    $filtered = array_filter($originalData, function($item) use ($filterStatus) {
                        return isset($item['status']) && $item['status'] === $filterStatus;
                    });
                    
                    $filteredData['data'] = array_values($filtered);
                    $filteredData['total'] = count($filtered);
                    $filteredData['original_total'] = count($originalData);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $filteredData,
                    'message' => 'Data surat tugas penugasan berhasil diambil'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API eksternal',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching surat tugas penugasan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available filter options for surat tugas penugasan
     */
    public function getSuratTugasFilterOptions(Request $request)
    {
        try {
            // Get all data first (without filters)
            $tempRequest = new Request();
            $dataResponse = $this->getSuratTugasPenugasan($tempRequest);
            $responseData = json_decode($dataResponse->getContent(), true);

            if (!$responseData['success']) {
                return $dataResponse;
            }

            $data = $responseData['data']['data'] ?? [];

            // Extract unique values for filters
            $unitKontributorOptions = [];
            $statusOptions = [];
            $tahunOptions = [];

            foreach ($data as $item) {
                // Unit Kontributor options
                if (isset($item['kode_unit_kontributor']) && isset($item['nama_unit_kontributor'])) {
                    $key = $item['kode_unit_kontributor'];
                    if (!isset($unitKontributorOptions[$key])) {
                        $unitKontributorOptions[$key] = [
                            'kode' => $item['kode_unit_kontributor'],
                            'nama' => $item['nama_unit_kontributor'] ?? $item['kode_unit_kontributor']
                        ];
                    }
                }

                // Status options
                if (isset($item['status']) && !in_array($item['status'], $statusOptions)) {
                    $statusOptions[] = $item['status'];
                }

                // Tahun options
                if (isset($item['tahun_pkpt']) && !in_array($item['tahun_pkpt'], $tahunOptions)) {
                    $tahunOptions[] = $item['tahun_pkpt'];
                }
            }

            // Sort the options
            ksort($unitKontributorOptions);
            sort($statusOptions);
            rsort($tahunOptions); // Reverse sort for years (newest first)

            return response()->json([
                'success' => true,
                'data' => [
                    'unit_kontributor' => array_values($unitKontributorOptions),
                    'status' => array_filter($statusOptions),
                    'tahun' => array_filter($tahunOptions)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching surat tugas filter options: ' . $e->getMessage()
            ], 500);
        }
    }
}
