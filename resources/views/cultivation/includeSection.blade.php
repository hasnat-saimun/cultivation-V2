
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Cultivation | @yield('backTitle')</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('/public/back-office/') }}/img/favicon.png">
    <!-- Normalize CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/normalize.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/main.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/bootstrap.min.css">
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/all.min.css">
    <!-- Select 2 CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/select2.min.css">
    <!-- Flaticon CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/fonts/flaticon.css">
    <script src="https://kit.fontawesome.com/163dbb3d41.js" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/32dcd4a478.js" crossorigin="anonymous"></script>
    <!-- Full Calender CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/fullcalendar.min.css">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/css/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/style.css">
    <!-- Modernize js -->
    <script src="{{ asset('/public/back-office/') }}/js/modernizr-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    
    <script type="text/javascript">
        $(document).ready( function () {
            $('#myTable').DataTable();
        } );
    </script>

    <style type="text/css">
        textarea.form-control{
            min-height: 120px;
        }
        .cultivation .form-control {
            padding: 1.75rem;
            font-size: 1.5rem;
        }
        .cultivation .form-select {
            padding: 0.5rem;
            font-size: 1.5rem;
            width: 100%;
            border: 1px solid #ced4da;
            color: #495057;
            border-radius:0.25rem;
        }
        .intro-button{
            font-size: 50px !important;
            margin-bottom: 0.5rem
        }
        .intro-box a{
            font-size: 16px;
        }
        .fw-bold{
            font-weight:bold;
        }
        .print-border{
            border-bottom:1px dotted gray; 
        }
        #bothChecked {
            display: none;
        }

        #bothBox:checked ~ #bothChecked{
            display: block;
        }

        #bothBox:checked ~ #bothUnchecked{
        display: none;
        }
        @media print
           {
            .print-border{
                border-bottom:0px dotted gray; 
            }
            
            @page {
                size: A4;
                margin: 2rem;
            }
            .page-break:last-child {
                page-break-after: avoid !important;
            }
            * { -webkit-print-color-adjust: exact !important; /*Chrome, Safari */ color-adjust: exact !important; /*Firefox*/ }
        
            html, body {
                width: 210mm;
                height: 297mm;
            }
            /* ... the rest of the rules ... */
            .marksheet{
                border: 3px solid black;
            }
              p.bodyText {font-family:georgia, times, serif;}
            
            .id-bg{
                background:url({{ asset('/public/back-office/img/') }}/idBg3.png) !important;
                
            }
            .id-bg1{
                background:url({{ asset('/public/back-office/img/') }}/idBg.png) !important;
            }
            .id-bg2{
                background:url({{ asset('/public/back-office/img/') }}/idBg2.png) !important;                
            }
            .id-bg,.id-bg1,.id-bg2{
                height: 100%;
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                border: 1px solid #d7d7d7;
                -webkit-print-color-adjust: exact; 
            }
            .img-thumbnail{
                background: #fff !important;
            }
           }
            .v-text {
                writing-mode: vertical-lr;
                transform: rotate(180deg);
                padding: 2.5rem 0.5rem;
            }
            .id-bg{
                background:url({{ asset('/public/back-office/img/') }}/idBg3.png);
                
            }
            .id-bg1{
                background:url({{ asset('/public/back-office/img/') }}/idBg.png);
            }
            .id-bg2{
                background:url({{ asset('/public/back-office/img/') }}/idBg2.png);                
            }
            .id-bg p,.id-bg1 p,.id-bg2 p{
                color: #000 !important;
            }
            .id-bg,.id-bg1,.id-bg2{
                height: 100%;
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                border: 1px solid #d7d7d7;
                -webkit-print-color-adjust: exact; 
            }

            .transparent{
                background:rgba(0,0,0,0.5);
            }
            .logosize{
                width:180px;
            }
            .form-check label {
                padding-left: 25px;
            }
    </style>