@if($studentData)
    <div class="col-10 mx-auto">
        <div class="card mt-4 card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="stdName" class="form-label fw-bold">Student Name:</label>
                    {{ $studentData->fullName }}
                </div>
                <div class="col-md-6 mb-2">
                    <label for="rollNumber" class="form-label fw-bold">Roll Number:</label>
                    {{ $studentData->rollNumber }}
                </div>
                @php
                    $sessionData= \App\Models\sessionManage::find($studentData->sessName);
                    $classData = \App\Models\classManage::find($studentData->className);
                @endphp
                <div class="col-md-6 mb-2">
                    <label for="stdName" class="form-label fw-bold">Class:</label>
                    {{ $classData->className }}
                </div>
                <div class="col-md-6 mb-2">
                    <label for="rollNumber" class="form-label fw-bold">Session:</label>
                    {{ $sessionData->session }}
                </div>
                <div class="col-md-6 mb-2 form-group">
                    <label for="feesType" class="form-label">Purpose/Note</label>
                    <select name="feesType" id="" class="form-control">
                        <option>-select-</option>
                    @if(!empty($feesData) && count($feesData)>0)
                        @foreach($feesData as $fd)
                        <option value="{{ $fd->id }}">{{ $fd->feesName}}</option>
                        @endforeach
                    @endif  
                    </select>
                </div>
                <div class="col-md-6 mb-2 form-group">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter collected amount" required>
                </div>
                <div class="mx-auto  gap-2 mt-4 form-group">
                    <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Submit</button>
                    <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                </div>
            </div>
        </div>
    </div>
@else
{{$studentData}}
    <div class="alert alert-info col-6 mx-auto mt-4">Sorry! No data found</div>
@endif