<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Report - {{ $careerTitle }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            padding: 0;
            line-height: 1.6;
        }

        h1, h2, h3 {
            color: #2c3e50;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .content {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Career Report</h1>
    <h2>{{ $careerTitle }}</h2>
    <p>Generated on {{ now()->format('F j, Y') }}</p>
</div>

@foreach($responses as $index => $response)
    <div class="section">
        @if(!empty($response['title']))
            <h1>{{ $response['title'] }}</h1>
        @endif
        @if(!empty($response['sub_title']))
            <h2>{{ $response['sub_title'] }}</h2>
        @endif
        <p class="content">{!! nl2br(e(!empty($response['description']) ? $response['description'] :  $response['response'])) !!}</p>
    </div>
@endforeach

</body>
</html>
