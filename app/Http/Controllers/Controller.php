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
            // Read the first row to determine the delimiter
            $firstRow = fgetcsv($handle, 1000);
            $delimiter = $this->detectDelimiter($firstRow[0]);

            // Rewind the file pointer to read from the beginning
            rewind($handle);

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                $csvData[] = $data;
            }

            fclose($handle);
        }
        $threshold = floatval($request->input('threshold')) * 100;
        // fgetcsv( resource $handle [, int $length = 0 [, string $delimiter = "," [, string $enclosure = '"' [, string $escape = "\" ]]]]): array
        // dd($csvData);
        $balances = [];
        $rowCount = count($csvData);
        $balances[] = 0;
        for ($i = 1; $i < $rowCount - 1; $i++) {
            $currentBalanceEnd = isset($csvData[$i][5]) ? floatval($csvData[$i][5]) : 0;
            $nextBalanceStart = isset($csvData[$i + 1][4]) ? floatval($csvData[$i + 1][4]) : 0;
            $balanceDifference = $nextBalanceStart - $currentBalanceEnd;
            $balances[] = $balanceDifference;
        }

        // Handle the last row
        // $var1=$csvData[$rowCount-1][5];
        // dd($var1);
        if ($rowCount > 0 && isset($csvData[$rowCount - 1][5])) {
            // Assuming the last BalanceStart is 0
            // dd($csvData[$rowCount - 1][5]);
            $balances[] = -$csvData[$rowCount - 1][5];

        }
        // dd($balances);
        $matrix = [];
        for ($i = 1; $i < $rowCount; $i++) {
            $matrix[] = [
                
                'GameId' => $csvData[$i][1] ?? 'N/A',
                'BalanceStart' => $csvData[$i][4] ?? 'N/A',
                'BalanceEnd' => $csvData[$i][5] ?? 'N/A',
                'Jugadas' => $csvData[$i][6] ?? 'N/A',
                'GananciaoDeposito' => $csvData[$i][7] ?? 'N/A',
                '$balances' => $balances[$i] ?? 'N/A',
                'JugadasGratis' => $csvData[$i][8] ?? 'N/A',
                'Hora' => $csvData[$i][11] ?? 'N/A',
            ];
        }
        // Filter the matrix based on the abrupt change until the search_value position
        $searchValue = floatval($request->input('search_value')) * -100;
        if ($searchValue !== null) {
            // $searchValue = floatval($searchValue) * -1;
            // Find the position of the row where search_value is located
            // $position = array_search($searchValue, array_column($matrix, '$balances'));
            $position = $this->findNearestPosition($matrix, '$balances', $searchValue);
            // Identify the abrupt change position
            $abruptChangePosition = $this->findAbruptChangePosition($matrix, $position, $threshold);
            // Filter the matrix based on the abrupt change position until search_value position
            $matrix = array_slice($matrix, $abruptChangePosition - 1, $position);
            // dd($abruptChangePosition,$position);
        }

        return view('welcome')->with('matrix', $matrix);
    }
    private function detectDelimiter($sampleRow)
    {
        // Check for common delimiters in CSV files
        $possibleDelimiters = [',', ';', '\t', '|'];

        foreach ($possibleDelimiters as $delimiter) {
            if (strpos($sampleRow, $delimiter) !== false) {
                return $delimiter;
            }
        }

        // Default to comma if no delimiter is detected
        return ',';
    }
    private function findNearestPosition($matrix, $column, $value)
    {
        $minDifference = PHP_INT_MAX;
        $nearestPosition = null;

        foreach ($matrix as $key => $row) {
            if (isset($row[$column])) {
                $currentValue = floatval($row[$column]);
                $difference = abs($currentValue - $value);

                if ($difference < $minDifference) {
                    $minDifference = $difference;
                    $nearestPosition = $key;
                }
            }
        }
        return $nearestPosition;
    }
    private function findAbruptChangePosition($matrix, $searchPosition, $threshold)
    {
        // $threshold = floatval($request->input('threshold'))*100;

        // Starting from the search position, find the first position with an abrupt change in BalanceStart
        for ($i = $searchPosition; $i >= 0; $i--) {
            if (isset($matrix[$i]['BalanceStart']) && isset($matrix[$i - 1]['BalanceStart'])) {
                $currentBalanceStart = floatval($matrix[$i]['BalanceStart']);
                $previousBalanceStart = floatval($matrix[$i - 1]['BalanceStart']);

                // Check if there's an abrupt change (e.g., difference greater than a threshold)
                if (abs($currentBalanceStart - $previousBalanceStart) > $threshold) {
                    return $i;
                }
            }
        }

        // If no abrupt change is found, return the beginning of the array
        return 0;
    }
}