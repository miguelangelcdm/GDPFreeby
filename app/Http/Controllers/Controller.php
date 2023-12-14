<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Validation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile as UploadFiles;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function processCsv(Request $request)
    {
        if ($request->file('csv_file')) {
            // New file uploaded
            $csvFile = $request->file('csv_file');
            $csvFileContent = $this->readCsvContent($csvFile);
            session(['csv_file_content' => $csvFileContent]);
            // $validation = Validation::first(); // Assuming you have only one record
            // if ($validation) {
            //     // If the record exists, increment the "total" field
            //     $validation->total = $validation->total ? $validation->total + 1 : 1;
            //     $validation->save();
            // } else {
            //     // If the record doesn't exist, you may want to create it with "total" set to 1
            //     Validation::create(['total' => 1, 'util' => 0]); // Assuming 'util' should also have a default value
            // }
            $this->storevali($request);
        } else {
            // Use the stored file content
            $csvFileContent = session('csv_file_content');
            // dd($csvFileContent);
            // Handle the case where the file content is not available
            if (!$csvFileContent) {
                // You might want to handle this case appropriately, such as redirecting back to the upload form.
                // For example:
                return redirect()
                    ->route('index')
                    ->with('error', 'No CSV file provided.');
            }
            $this->storevali($request);
        }

        // Now you can proceed with $csvFileContent
        $csvFile = fopen('php://memory', 'r+');
        foreach ($csvFileContent as $line) {
            fwrite($csvFile, $line);
        }
        rewind($csvFile);
        // dd();
        // Continue with the rest of your CSV processing logic
        if (($handle = $csvFile) !== false) {
            // Read the first row to determine the delimiter
            $firstRow = fgetcsv($handle, 1000);
            $delimiter = $this->detectDelimiter($firstRow[0]);

            // Rewind the file pointer to read from the beginning
            rewind($handle);

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $csvData[] = $data;
            }

            fclose($handle);
        }
        $threshold = floatval($request->input('threshold')) * 100;
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
                'Hora' => $csvData[$i][10] ?? 'N/A',
                'ObjectId' => $csvData[$i][11] ?? 'N/A',
            ];
        }
        // dd($matrix);

        // Filter the matrix based on the abrupt change until the search_value position
        $searchValue = floatval($request->input('search_value')) * -100;
        if ($searchValue !== null) {
            // Find the position of the row where search_value is located
            $position = $this->findNearestPosition($matrix, '$balances', $searchValue);
            // Identify the abrupt change position
            $abruptChangePosition = $this->findAbruptChangePosition($matrix, $position, $threshold);
            // Filter the matrix based on the abrupt change position until search_value position
            $matrix = array_slice($matrix, $abruptChangePosition - 1, $position - $abruptChangePosition + 2);
            // dd($abruptChangePosition,$position);
        }
        $games = array_unique(array_column($matrix, 'GameId'));
        $gamble = array_unique(array_column($matrix, 'Jugadas'));
        $convertedValues = array_map(function ($value) {
            // Convert the string to float and divide by 100
            return (float) $value / 100;
        }, $gamble);
        sort($convertedValues);
        // dd($convertedValues);
        $parsedValues = array_map(function ($value) {
            // Explode the string by "G" and parse the second part to an integer
            return (int) explode('G', $value)[1];
        }, $games);
        // dd($parsedValues);
        // dd($games);
        $games2 = Game::all();
        $gamesArray = $games2->toArray();
        $gameNames = [];
        foreach ($parsedValues as $parsedValue) {
            $found = false; // Flag to check if $parsedValue is found
            foreach ($gamesArray as $game) {
                if ($game['id'] === $parsedValue) {
                    $gameNames[] = $game['name'];
                    $found = true;
                    break; // No need to continue checking once found
                }
            }
            if ($found == false) {
                $gameNames[] = $parsedValue; // Add $parsedValue if not found in $gamesArray
            }
        }
        $currency = $request->input('currency');
        // dd($currency);
        $request->session()->put('processedData', [
            'matrix' => $matrix,
            'searchValue' => $searchValue,
            'threshold' => $threshold,
            'floro' => $this->floro($gameNames, $currency, $matrix, $convertedValues),
        ]);
        $valtotales = Validation::count();
        return view('results')
            ->with('matrix', $matrix)
            ->with('searchValue', $searchValue)
            ->with('threshold', $threshold)
            ->with('currency',$currency)
            ->with('floro', $this->floro($gameNames, $currency, $matrix, $convertedValues))
            ->with('valtotales', $valtotales);
    }
    public function floro($gameNames, $currency, $matrix, $convertedValues)
    {
        if (count($gameNames) == 1) {
            if ($currency == 1) {
                $floro = 'El usuario <colocar el usuario aquí> tenía un balance inicial de ' . floatval($matrix[0]['BalanceStart']) / 100 . ' pesos, con apuestas de ' . implode(', ', $convertedValues) . ' pesos en el juego ' . reset($gameNames) . ' , fue aumentando su balance hasta ' . floatval($matrix[count($matrix) - 1]['BalanceEnd']) / 100 . ' pesos.';
            } else {
                $floro = 'El usuario <colocar el usuario aquí> tenía un balance inicial de ' . floatval($matrix[0]['BalanceStart']) / 100 . ' soles, con apuestas de ' . implode(', ', $convertedValues) . ' soles en el juego ' . reset($gameNames) . ' , fue aumentando su balance hasta ' . floatval($matrix[count($matrix) - 1]['BalanceEnd']) / 100 . ' soles. Es conforme';
            }
        } else {
            $lastGame = array_pop($gameNames);
            if ($currency == 1) {
                $floro = 'El usuario <colocar el usuario aquí> tenía un balance inicial de ' . floatval($matrix[0]['BalanceStart']) / 100 . ' pesos, con apuestas de ' . implode(', ', $convertedValues) . ' pesos en los juegos ' . implode(', ', $gameNames) . ' y ' . $lastGame . ' , fue aumentando su balance hasta ' . floatval($matrix[count($matrix) - 1]['BalanceEnd']) / 100 . ' pesos.';
            } else {
                $floro = 'El usuario <colocar el usuario aquí> tenía un balance inicial de ' . floatval($matrix[0]['BalanceStart']) / 100 . ' soles, con apuestas de ' . implode(', ', $convertedValues) . ' soles en los juegos ' . implode(', ', $gameNames) . ' y ' . $lastGame . ' , fue aumentando su balance hasta ' . floatval($matrix[count($matrix) - 1]['BalanceEnd']) / 100 . ' soles. Es conforme';
            }
        }
        return $floro;
    }
    public function utilstorage(Request $request)
    {
        // $request->validate([
        //     'monto' => 'required',
        //     'threshold' => 'required',
        // ]);

        $latestValidation = Validation::where('monto', $request->search_value)
            ->where('threshold', $request->threshold)
            ->latest('created_at')
            ->first();
        if ($latestValidation) {
            // Update the most recent matching record
            $latestValidation->util = '1';
            $latestValidation->save();
        } else {
            // Create a new record if not found
            $validation = new Validation();
            $validation->monto = $request->search_value;
            $validation->threshold = $request->threshold;
            $validation->currency = $request->currency;
            $validation->save();
        }
        // $util = false;
        // $validation = Validation::first(); // Assuming you have only one record

        // if ($validation) {
        //     // If the record exists, increment the "util" field
        //     $validation->util = $validation->util ? $validation->util + 1 : 1;
        //     $validation->save();
        //     $util = true;
        // } else {
        //     // If the record doesn't exist, create it with "util" set to 1
        //     $validation = Validation::create(['total' => 0, 'util' => 1]); // Assuming 'util' should have a default value of 1
        //     $util = true;
        // }
        // $valtotales = $validation->total;
        $valtotales = Validation::count();
        $util=1;
        // Pass the 'util' variable directly to the view using compact
        return view('welcome', compact('util'), compact('valtotales'));
    }
    public function readCsvContent($file)
    {
        // Check if $file is an instance of UploadedFile
        if ($file instanceof UploadFiles) {
            $filePath = $file->path();
            // dd($filePath);
            return file($filePath);
        } else {
            // Assume $file is an array containing CSV content
            return $file;
        }
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
    public function storevali($request): void
    {
        // $request->validate([
        //     // 'monto' => 'required',
        //     'threshold' => 'required',
        // ]);
        $validation = new Validation();
        $validation->monto = $request->search_value;
        $validation->threshold = $request->threshold;
        $validation->currency = $request->currency;
        $validation->save();
    }
}
