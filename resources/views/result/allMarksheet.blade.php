@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
    <div class="main-website">
            <div class="main-content">
                <!-- header part start -->
                <div class="header">
                    <div class="container-fluid">
                        <div class="row">
                          <div class="col-12 text-center">
                          @php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->institueName;
        $location           = $serverData->address;
        $officeMobile       = $serverData->officeMobile;
        $officeMail         = $serverData->officeEmail;
        $principalSign      = $serverData->principalSign;
        $logo               = $serverData->logo;
        $favicon            = $serverData->favicon;
        $avatar             = $serverData->avatar;
        $einNumber          = $serverData->einNumber;
        $establishDate      = $serverData->establishDate;
    else:
        $serverId           = "";
        $insName            = "Sonar Bangla College";
        $location           = "Poyat, Burichong, Cumilla";
        $officeMobile       = "01716841785";
        $officeMail         = "";
        $principalSign      = "";
        $logo               = "";
        $favicon            = "";
        $avatar             = "";
        $einNumber          = "434713";
        $establishDate      = "";
    endif;
@endphp
<div class="fw-bold h2">{{ $insName }}</div>
<div class="fw-bold p">{{ $location }}</div>
<div class="fw-bold p">Tel:- {{ $officeMobile }}, EIN:- {{ $einNumber }}</div>
                          </div>
                    </div>
                </div>
                <!-- header part end-->

                <!-- Result section start -->
                <div class="result-section">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="result-text text-center mt-5">
                                    <h2>SSC/Dakhil/Equivalent Result Publication 2014</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- mark sheet start -->
                <div class="">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="table-responsive dark-border">
                                    <table class="w-100 table-striped table-bordered text-center table">
                                        <tr class="table-dark text-dark">
                                            <td rowspan="3"><b>SL</b></td>
                                            <td rowspan="3"><b>Student Id</b></td>
                                            <td rowspan="3"><b>Name</b></td>
                                            <td colspan="32" ><b>Subject</b></td>
                                            <td rowspan="3"><b>Mark</b></td> 
                                            <td rowspan="3"><b>Grade</b></td>
                                            <td rowspan="3"><b>GPA</b></td>
                                        </tr>
                                        <tr class="table-dark text-dark">
                                          @php
                                              $subjectId = \App\Models\Subject::orderBy('id','ASC')->get();
                                          @endphp
                                          @if(!empty($subjectId))
                                              @foreach($subjectId as $sub)
                                              <td colspan="4"><b>{{ $sub->subjectName }}</b></td>
                                              @endforeach
                                          @endif
                                        </tr>
                                        <tr class="table-dark text-dark">
                                          
                                          @if(!empty($subjectId))
                                              @foreach($subjectId as $sb)           
                                              <td><b>CQ</b></td>
                                              <td><b>MCQ</b></td>
                                              <td><b>P</b></td>
                                              <td><b>TOTAL</b></td>
                                               @endforeach
                                            @endif                                 
                                        </tr>
                                        <!-- <tr>
                                            <th >101</th>
                                            <td>2025000007</td>
                                            <td>Hasnat</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>

                                            <td>67</td>
                                            <td>89</td>
                                            <td>45</td>
                                            <td>78</td>

                                            <td>500</td>
                                            <td>A+</td>
                                            <td>5.00</td>
                                        </tr>
                                        <tr>
                                            <th class="w-25">101</th>
                                            <td>2025000007</td>
                                            <td>Hasnat</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>100</td>
                                            <td>80</td>
                                            <td>96</td>
                                            <td>78</td>
                                            <td>67</td>
                                            <td>89</td>
                                            <td>45</td>
                                            <td>78</td>
                                            <td >500</td>
                                            <td>A+</td>
                                            <td>5.00</td>
                                        </tr> -->

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- mark sheet end -->
                <!-- footer  section start -->
                <!-- footer  section end -->
            </div>
        </div>
    </div>
@endsection