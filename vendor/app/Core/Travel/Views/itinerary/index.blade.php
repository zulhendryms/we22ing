<html>
    <head>
        <title>Itinerary Summary</title>
        <style>
            * {
                font-family: Helvetica;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            tr {
                page-break-inside:avoid;
            }
            td {
                padding: 5px;
                font-size: 11px;
            }
            .bordered td {
                border: 1px solid gray;
            }
            .logo {
                width: 2cm;
                height: auto;
            }
            .title {
                text-decoration: underline;
            }
            .header_logo {
                border-bottom: 3px solid black;
                padding-bottom: 10px;
            }
            .text-center, .text-center td {
                text-align: center;
            }
            .text-left, .text-left td {
                text-align: left;
            }
            .text-right, .text-right td {
                text-align: right;
            }
            .vtop {
                vertical-align: text-top;
            }
            .vbottom {
                vertical-align: text-bottom;
            }
            .border-top-none, .border-top-none td {
                border-top: none !important;
            }
            .border-left-none, .border-left-none td {
                border-left: none !important;
            }
            .border-bottom-none, .border-bottom-none td {
                border-bottom: none !important;
            }
            .border-right-none, .border-right-none td {
                border-right: none !important;
            }
            .strong, .strong td {
                font-weight: 800;
            }
        </style>
    </head>
    <body>
        @include('Core\Travel::itinerary.header')
        @include('Core\Travel::itinerary.general')
        @include('Core\Travel::itinerary.flight')
        @include('Core\Travel::itinerary.hotel')
        @include('Core\Travel::itinerary.transport')
        @include('Core\Travel::itinerary.details')
        @include('Core\Travel::itinerary.notes')
    </body>
</html>