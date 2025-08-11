
    <div class="list-group rounded-0 mb-4 border border-success">
        <div class="p-2 bg-success h5"><i class="fa-duotone fa-user"></i> Education Ministar</div>
        <div class="p-2 text-center">
            @if(!empty($Datakey->eduMinImg)) 
            <img class="w-75" alt="Education Minister" src="{{ asset('public/upload/image/webHomepage').'/'.$Datakey->eduMinImg }}" />
            @else
            <img class="w-75" alt="Education Minister" src="{{ asset('/public/') }}/img/chairmanOfBoard.jpg" />
            @endif

            <p class="fw-bold my-0">{{$Datakey->eduMinName}}</p>
            <p class="fw-bold my-0 small">{{$Datakey->eduMinDetail}}</p>
            <a href="#"> Details </a>
        </div>
    </div>
    <div class="list-group rounded-0 my-4 border border-success">
        <div class="p-2 bg-success h5"><i class="fa-duotone fa-user-tie-hair"></i> Board Chairman</div>
         <div class="p-2 text-center">
            @if(!empty($Datakey->boardChairmanImg)) 
            <img class="w-75" alt="Board Chairman" src="{{ asset('public/upload/image/webHomepage').'/'.$Datakey->boardChairmanImg }}" />
            @else
            <img class="w-75" alt="Board Chairman" src="{{ asset('/public/') }}/img/chairmanOfBoard.jpg" />
            @endif

            <p class="fw-bold my-0">{{$Datakey->boardChairmanName}}</p>
            <p class="fw-bold my-0 small">{{$Datakey->boardChairmanDetail}}</p>
            <a href="#"> Details </a>
        </div>
    </div>
    <div class="list-group rounded-0 border border-success">
        <div class="p-2 bg-success h5"><i class="fa-regular fa-user-tie"></i> Principal</div>
        <div class="p-2 text-center">
            @if(!empty($Datakey->principalImg)) 
            <img class="w-75" alt="Principal" src="{{ asset('public/upload/image/webHomepage').'/'.$Datakey->principalImg }}" />
            @else
            <img class="w-75" alt="Principal" src="{{ asset('/public/') }}/img/chairmanOfBoard.jpg" />
            @endif

            <p class="fw-bold my-0">{{$Datakey->principalName}}</p>
            <p class="fw-bold my-0 small">{{$Datakey->principalDetail}}</p>
            <a href="#"> Details </a>
        </div>
    </div>
    <div class="list-group rounded-0 my-4 small">
        <div class="bg-success p-2 h5 mb-0"><i class="fa-light fa-globe-pointer"></i> Important Link</div>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> গণপ্রজাতন্ত্রী বাংলাদেশ সরকার</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> শিক্ষা মন্ত্রণালয়</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> মাধ্যমিক ও উচ্চশিক্ষা অধিদপ্তর</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> মাধ্যমিক ও উচ্চ মাধ্যমিক শিক্ষা বোর্ড</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> মাধ্যমিক ও উচ্চ শিক্ষা বিভাগ</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> ই-বুক</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> আই-বুক</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-sharp fa-light fa-chevrons-right"></i> মাউশি</a>
    </div>
    <div class="list-group rounded-0 my-4">
        <div class="bg-success p-2 h5 mb-0"><i class="fa-solid fa-message-music"></i> National Song</div>
        <a href="#" class="list-group-item list-group-item-action">
            <audio controls="" style="width: 100%;" class="mt-3">
                <source src="music/bd_national_anthem.mp3" type="audio/mpeg" />
            </audio>
        </a>
    </div>
    <div class="list-group rounded-0 my-4 small">
        <div class="bg-success p-2 h5 mb-0"><i class="fa-brands fa-usps"></i> Internal eService</div>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-solid fa-envelopes-bulk"></i> Webmail</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-solid fa-user"></i> Teacher Login</a>
        <a href="#" class="list-group-item list-group-item-action"><i class="fa-solid fa-square-question"></i> Complain/Suggestion</a>
    </div>
    <div class="list-group rounded-0 my-4 small">
        <div class="bg-success p-2 h5 mb-0"><i class="fa-light fa-users"></i> Visitor Corner</div>
        <a href="#" class="list-group-item list-group-item-action"> <span class="fw-bold">Today Visitor:</span> <span class="badge text-bg-success">35</span> </a>
        <a href="#" class="list-group-item list-group-item-action"> <span class="fw-bold">Total Visitor:</span> <span class="badge text-bg-success">364567</span> </a>
        <a href="#" class="list-group-item list-group-item-action"> <span class="fw-bold">Your IP Address:</span> <span class="badge text-bg-info"></span> </a>
    </div>
