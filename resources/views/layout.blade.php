<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Contacts</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  {{--@vite(['resources/css/app.css', 'resources/js/app.js'])--}}
    <!-- public folder css Or js start -->
        <link rel="stylesheet" href="{{ asset('public/build/assets/app-Cc6o6Mxg.css') }}">
        <script src="{{ asset('public/build/assets/app-C0G0cght.js') }}" defer></script>
    <!-- public folder css Or js end -->
</head>
<body class="font-sans antialiased bg-gray-50">
  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">@yield('title','Contacts')</h1>
    @if(session('ok')) <div class="mb-4 p-3 bg-green-100 border border-green-200 rounded">{{ session('ok') }}</div> @endif
    @yield('content')
  </div>
</body>
</html>
