<!doctype html>
<html class="no-js" lang="">
<head>
    @include('cultivation.includeSection')
    <style>
    /* main website */
        .main-website {
        background-color: #fff;
        box-sizing: border-box;
        width: 100%;
        margin: 10px auto;
        }
        .secend-main-div {
        background-color: #eeeeee;
        box-sizing: border-box;
        margin-top: 21px;
        padding-top: 3px;
        padding-bottom: 14px;
        }
        .main-content {
        box-sizing: border-box;
        background-color: #fff;
        padding-top: 14px;
        padding-bottom: 33px;
        border-radius: 12px;
        }
        .header {
        border-radius: 42px 0px 0px;
        overflow: hidden;
        }
        /* logo part start */
        .main-logo-part {
        text-align: end;
        padding: 31px 0px;
        box-sizing: border-box;
        }


        /*logo part end */

        /* header-top start */
        .header-top1{
        margin-bottom: -15px;
        margin-top: 20px;
        }
        .header-top1 p{
        font-weight: 400;
        }
        /* header-top end */
        /* deafult css */
        img{
            max-width: 100%;
        }
        p{
        line-height: .4 !important;
        padding: auto;
        }

        /* deafult css */
        /* header part start */
        /* header top start */
        .header-top {
        background-position: right center;
        background-repeat: no-repeat ;
        padding-top: 11px;
        
        background-size: contain;
        
        padding-left: 14px;
        }
        .header-top h1,h2,h3,h4,h5,h6 {
        line-height: .4 !important;
        font-size: 23px;
        font-weight: bold;
        }
        /* header top end */
        /* header middle start */

        .header-middle h3 {
        color: white;
        font-size: 29px;
        font-weight: bold;
        padding-left: 15px;
        padding-top: 3px;
        padding-bottom: 10px;
        letter-spacing: 1px;
        word-spacing: 6px;
        padding-right: 58px;
        border-top: 1px solid lightgray;
        line-height: 26px;
        margin-bottom: 0;
        }
        /* header middle end */

        /* header- buttom start */
        .header-bottomm {
        
        padding: 6px 0;
        padding-right: 13px;
        box-sizing: border-box;
        }
        .header-bottomm p {
        margin: 0;
        font-weight: 600;
        color: white;
        }
        /* header- buttom end */
        /* Result section start */
        .result-text{

        }
        .result-text h2 {
        font-weight: bold;
        border-bottom: 4px solid #ddd;
        padding-bottom: 10px;
        }
        /* Result section end */

        /* table section start*/
        .table{

        }
        .table tr{
        background-color: #f0f0f0;
        }
        .table tr td {
        padding: 2px;
        border: 5px solid #fff;
        }
        /* table section end*/

        /* grade table start */
        .marksheet-table td{
        border-right: 3px solid white;
        }
        /* grade table end */

        /* search link  start*/
        .search-text a {
        font-weight: bold;
        font-size: 22px;
        color: #6d66b8;
        }
        /* search link  end*/

        /* footer section start */

        .footer-div {
        background-color: #eff3ee;
        border-top: 5px solid #78bf63;
        padding-top: 8px;
        }
        .copyright{

        }
        .copyright p {
        margin: 0;
        padding: 26px 19px;
        font-weight: 400;
        }
        .dark-border table td,th{
            border: 2px solid #000 !important;
            padding: .9rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Preloader Start Here -->
    <div id="preloader"></div>
    <!-- Preloader End Here -->
    <div id="wrapper" class="wrapper bg-ash">
        <!-- Page Area Start Here -->
        <div class="dashboard-page-one">
            <div class="dashboard-content-one">
                <!-- Breadcubs Area Start Here -->
                <div class="breadcrumbs-area d-print-none">
                    <h3>Result Management Panel</h3>
                    <ul>
                        <li>
                            <a href="{{ route('resultPart') }}">Home</a>
                        </li>
                        <li>@yield('backTitle')</li>
                    </ul>
                </div>
                <!-- Breadcubs Area End Here -->
                @yield('backIndex')
                <!-- Footer Area Start Here -->
                <footer class="footer-wrap-layout1 d-print-none">
                    @include('cultivation.footer')
                </footer>
                <!-- Footer Area End Here -->
            </div>
        </div>
        <!-- Page Area End Here -->
    </div>
    
    <!-- jquery-->
    <!-- Plugins js -->
    <script src="{{ asset('/public/back-office/') }}/js/plugins.js"></script>
    <!-- Popper js -->
    <script src="{{ asset('/public/back-office/') }}/js/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('/public/back-office/') }}/js/bootstrap.min.js"></script>
    <!-- Counterup Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.counterup.min.js"></script>
    <!-- Moment Js -->
    <script src="{{ asset('/public/back-office/') }}/js/moment.min.js"></script>
    <!-- Waypoints Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.waypoints.min.js"></script>
    <!-- Scroll Up Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.scrollUp.min.js"></script>
    <!-- Full Calender Js -->
    <script src="{{ asset('/public/back-office/') }}/js/fullcalendar.min.js"></script>
    <!-- Select 2 Js -->
    <script src="{{ asset('/public/back-office/') }}/js/select2.min.js"></script>
    <!-- Chart Js -->
    <script src="{{ asset('/public/back-office/') }}/js/Chart.min.js"></script>
    <!-- Custom Js -->
    <script src="{{ asset('/public/back-office/') }}/js/main.js"></script>

</body>
</html>