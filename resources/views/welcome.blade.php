<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <link rel="stylesheet" href="//unpkg.com/swagger-ui-dist@3/swagger-ui.css">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Backend Test</title>

    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script async="async" src="//unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
<script>
  window.onload = function () {
    const ui = SwaggerUIBundle({
      url: '{{ url('/api/v1/documentation.yml') }}',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [
        SwaggerUIBundle.presets.apis,
      ],
      plugins: [
        SwaggerUIBundle.plugins.DownloadUrl
      ],
      layout: 'BaseLayout'
    })

    window.ui = ui
  }
</script>
</body>
</html>
