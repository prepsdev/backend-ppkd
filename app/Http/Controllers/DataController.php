<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Data;

class DataController extends Controller
{
    /**
     * Get distinct tema with related topik data
     */
    public function getTemaWithTopik()
    {
        try {
            // Ambil semua tema unik
            $temaList = Data::select('tema')->distinct()->pluck('tema');

            $result = $temaList->map(function ($tema) {
                // Ambil semua topik per tema
                $topikList = Data::select('topik', 'topik_uri')
                    ->where('tema', $tema)
                    ->distinct()
                    ->get();

                return [
                    'tema' => $tema,
                    'topik_list' => $topikList
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tema data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data by topik_uri parameter
     */
    public function getDataByTopik($topikUri)
    {
        try {
            $data = DB::table('data')
                ->select('indikator', 'indikator_uri', 'deskripsi', 'sumber', 'lastupdate')
                ->where('topik_uri', $topikUri)
                ->distinct()
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found for this topik'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching topik data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data by indikator_uri parameter
     */
    public function getDataByIndikator($indikatorUri)
    {
        try {
            $data = DB::table('data')
                ->where('indikator_uri', $indikatorUri)
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found for this indikator'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching indikator data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search indikator by query string
     */
    public function searchIndikator(Request $request)
    {
        try {
            $query = $request->get('q');
            $limit = $request->get('limit', null);

            if (!$query || strlen($query) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukan minimal 3 kata untuk melakukan pencarian indikator terkait'
                ], 400);
            }

            $searchQuery = DB::table('data')
                ->select('indikator', 'indikator_uri', 'deskripsi', 'sumber', 'lastupdate', 'tema', 'topik', 'topik_uri')
                ->where('indikator', 'LIKE', '%' . $query . '%')
                ->distinct();

            // Get total count for pagination info
            $totalCount = DB::table('data')
                ->select('indikator', 'indikator_uri', 'deskripsi', 'sumber', 'lastupdate', 'tema', 'topik', 'topik_uri')
                ->where('indikator', 'LIKE', '%' . $query . '%')
                ->distinct()
                ->count();

            // Apply limit if specified
            if ($limit) {
                $searchQuery = $searchQuery->limit($limit);
            }

            $results = $searchQuery->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'total_count' => $totalCount,
                'showing_count' => $results->count(),
                'has_more' => $limit && $totalCount > $limit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching indikator: ' . $e->getMessage()
            ], 500);
        }
    }
}