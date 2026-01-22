<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>@yield('title', 'SkillSwap')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  @stack('styles')
</head>

<body class="@yield('body-class', '')">
  @yield('content')

  @stack('scripts')
</body>
</html>
