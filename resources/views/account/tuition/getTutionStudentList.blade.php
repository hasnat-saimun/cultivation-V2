@if(!empty($studentData))
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
                    {{ $classData->className ?? '' }}
                </div>
                <div class="col-md-6 mb-2">
                    <label for="rollNumber" class="form-label fw-bold">Session:</label>
                    {{ $sessionData->session ?? '' }}
                </div>
                <div class="col-12">
                    <div id="feesRows" class="row g-3 align-items-end">
                        <div class="col-12 row fees-row">
                            <div class="col-md-6 mb-2 form-group">
                                <label class="form-label">Purpose/Note</label>
                                <select name="feesType[]" class="form-control" required>
                                    <option value="">-select-</option>
                                    @if(!empty($feesData) && count($feesData)>0)
                                        @foreach($feesData as $fd)
                                            <option value="{{ $fd->id }}" data-amount="{{ $fd->feesAmount }}">{{ $fd->feesName}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 form-group">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control amount-input" name="amount[]" placeholder="Enter amount" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-2 mb-2 form-group d-flex align-items-end">
                                <button type="button" class="btn btn-outline-primary me-2 add-row">Add</button>
                                <button type="button" class="btn btn-outline-danger remove-row" disabled>Remove</button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <div class="h5">Total: <span id="feesTotal">0.00</span></div>
                    </div>
                </div>
                <div class="mx-auto gap-2 mt-4 form-group">
                    <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Submit</button>
                    <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-info col-6 mx-auto mt-4 text-center">Sorry! No data found</div>
@endif
