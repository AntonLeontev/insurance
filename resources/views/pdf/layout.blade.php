<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		body {
			font-family: DejaVu Sans;
		}
		.page-break {
			page-break-after: always;
		}
		.page-break-before {
			page-break-before: always;
		}
		.page-break-inside {
			page-break-inside: avoid;
		}
	</style>
	
	@stack('styles')
</head>
<body>
	@yield('content')
</body>
</html>
