<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Laravel</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

  <!-- Styles -->
  @vite('resources/css/app.css')
</head>

<body class="dark-mode antialiased">
  <div
    class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">
    @if (Route::has('login'))
      <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
        @auth
          <a href="{{ url('/home') }}"
            class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Home</a>
        @else
          <a href="{{ route('login') }}"
            class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Log
            in</a>

          @if (Route::has('register'))
            <a href="{{ route('register') }}"
              class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Register</a>
          @endif
        @endauth
      </div>
    @endif

    <div class="max-w-7xl mx-auto p-6 lg:p-8 flex-row flex gap-x-16">
      <div class="flex flex-col justify-center">
        @if (isset($matrix) && !empty($matrix))
          <div
            class="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500 my-6 ">
            <div style="width:100%">
              <label for="message" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">El
                floro</label>
              <textarea id="message" rows="8"
                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="El usuario  tenía un balance inicial de  pesos, con apuestas de  pesos en el juego  fue aumentando su balance hasta pesos."></textarea>
            </div>
          </div>
        @endif
      </div>

      <div class="relative overflow-x-auto shadow-md sm:rounded-lg overflow-auto" style="max-height: 80vh">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <caption class="p-5 text-lg font-semibold text-left text-gray-900 bg-white dark:text-white dark:bg-gray-800">
            Hola mundo:
            <p class="mt-1 text-sm font-normal text-gray-500 dark:text-gray-400">Se esta validando desde el balance
              inicial ___ hasta _____, donde un total de _____ registros filtrados para la validación. Tomesé siempre
              con precausión el rango asignado por un threshold de 4000 pesos de cambio abrupto en balance.</p>
          </caption>
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
            <tr>
              <th scope="col" class="px-6 py-3">GameId</th>
              <th scope="col" class="px-6 py-3">Balance Inicial</th>
              <th scope="col" class="px-6 py-3">Balance Final</th>
              <th scope="col" class="px-6 py-3">Jugadas</th>
              <th scope="col" class="px-6 py-3">Ganancia o Deposito</th>
              <th scope="col" class="px-6 py-3">Diferencia de balances</th>
              <th scope="col" class="px-6 py-3">Jugadas Gratis</th>
              <th scope="col" class="px-6 py-3">Hora</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($matrix as $row)
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-6 py-4">{{ $row['GameId'] }}</td>
                <td class="px-6 py-4">{{ $row['BalanceStart'] }}</td>
                <td class="px-6 py-4">{{ $row['BalanceEnd'] }}</td>
                <td class="px-6 py-4">{{ $row['Jugadas'] }}</td>
                <td class="px-6 py-4">{{ $row['GananciaoDeposito'] }}</td>
                <td class="px-6 py-4">{{ $row['$balances'] }}</td>
                <td class="px-6 py-4">{{ $row['JugadasGratis'] }}</td>
                <td class="px-6 py-4">{{ $row['Hora'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>

</html>
