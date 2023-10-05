<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function processCsv(Request $request)
    {
        $csvFile = $request->file('csv_file');

        $csvData = [];
        if (($handle = fopen($csvFile, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
                $csvData[] = $data;
            }
            fclose($handle);
        }
        $balances = [];
        $rowCount = count($csvData);
        $balances[] = 0;
        for ($i = 1; $i < $rowCount - 1; $i++) {
            $currentBalanceEnd = isset($csvData[$i][5]) ? floatval($csvData[$i][5]) : 0;
            $nextBalanceStart = isset($csvData[$i + 1][4]) ? floatval($csvData[$i + 1][4]) : 0;
            $balanceDifference = $nextBalanceStart - $currentBalanceEnd;
            $balances[] = $balanceDifference;
        }
        // Handle the last row separately
        if ($rowCount > 0 && isset($csvData[$rowCount - 1][5])) {
            // Assuming the last BalanceStart is 0
            $balances[] = -$csvData[$rowCount - 1][5];
        }

        $matrix = [];
        for ($i = 1; $i < $rowCount; $i++) {
            $matrix[] = [
                'GameId' => $csvData[$i][1] ?? 'N/A',
                'BalanceStart' => $csvData[$i][4] ?? 'N/A',
                'BalanceEnd' => $csvData[$i][5] ?? 'N/A',
                'Jugadas'=> $csvData[$i][6] ?? 'N/A',
                'GanaciaoDeposito'=> $csvData[$i][7] ?? 'N/A',
                '$balances' => $balances[$i] ?? 'N/A',
            ];
        }
        // Filter the matrix based on the abrupt change until the search_value position
        $searchValue = $request->input('search_value');
        if ($searchValue !== null) {
            $searchValue = floatval($searchValue)*-1; 
            // Find the position of the row where search_value is located
            $position = array_search($searchValue, array_column($matrix, '$balances'));
            // Identify the abrupt change position
            $abruptChangePosition = $this->findAbruptChangePosition($matrix, $position);
            // Filter the matrix based on the abrupt change position until search_value position
            $matrix = array_slice($matrix, $abruptChangePosition-1, $position);
            // dd($abruptChangePosition,$position);
        }
        

        return view('welcome')->with('matrix', $matrix);
    }
    private function findAbruptChangePosition($matrix, $searchPosition)
    {
        // Starting from the search position, find the first position with an abrupt change in BalanceStart
        for ($i = $searchPosition; $i >= 0; $i--) {
            if (isset($matrix[$i]['BalanceStart']) && isset($matrix[$i - 1]['BalanceStart'])) {
                $currentBalanceStart = floatval($matrix[$i]['BalanceStart']);
                $previousBalanceStart = floatval($matrix[$i - 1]['BalanceStart']);

                // Check if there's an abrupt change (e.g., difference greater than a threshold)
                if (abs($currentBalanceStart - $previousBalanceStart) > 400000) {
                    return $i;
                }
            }
        }

        // If no abrupt change is found, return the beginning of the array
        return 0;
    }
}