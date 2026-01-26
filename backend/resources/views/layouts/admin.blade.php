<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', 'Admin – SkillSwap')</title>

  {{-- shared admin styles --}}
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  @stack('styles')
</head>

<body class="@yield('body-class')">
    <div style="background:red;color:white;padding:6px;text-align:center;">
  ADMIN LAYOUT ACTIVE
</div>

  @yield('content')
  @stack('scripts')
</body>
</html>
