@extends('account.include')
@section('backTitle')
Classwise Fees Setup
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card shadow p-2 border-0">
            <div class="card-body">
                <h4 class="mb-3">Classwise Fees Setup</h4>
                <p class="text-muted mb-4">Configure default setup amount by class and fee type. These values are auto-used during student fee collection.</p>

                @if(session()->has('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('saveClassWiseFeeSetup') }}" id="classwiseSetupForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group">
                            <label class="form-label">Class</label>
                            <select class="form-control select2" name="classId" id="classId" required>
                                <option value="">-Select Class-</option>
                                @foreach($classData as $cls)
                                    <option value="{{ $cls->id }}" {{ old('classId') == $cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="loadedSetupInfo" class="p-2 alert-info mt-2" style="display:none;"></div>

                    <div id="setupRows" class="mt-2">
                        <div class="row setup-row align-items-end">
                            <div class="col-md-5 form-group">
                                <label class="form-label">Fee Type</label>
                                <select class="form-control" name="feesType[]" required>
                                    <option value="">-Select Fee Type-</option>
                                    @foreach($feesData as $fee)
                                        <option value="{{ $fee->id }}">{{ $fee->feesName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="form-label">Setup Amount</label>
                                <input type="number" name="setupAmount[]" class="form-control" min="0.01" step="0.01" placeholder="Enter amount" required>
                            </div>
                            <div class="col-md-3 form-group d-flex">
                                <button type="button" class="btn btn-outline-primary me-2 add-setup-row">Add</button>
                                <button type="button" class="btn btn-outline-danger remove-setup-row" disabled>Remove</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save Classwise Setup</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow p-2 border-0 mt-4">
            <div class="card-body">
                <h5 class="mb-3">Existing Classwise Setup</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Fee Type</th>
                                <th>Setup Amount</th>
                                <th style="width:130px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($setupRows as $row)
                                <tr>
                                    <td>{{ $row->class_name }}</td>
                                    <td>{{ $row->fee_name }}</td>
                                    <td>{{ number_format((float)$row->setup_amount, 2) }}</td>
                                    <td>
                                        <a href="{{ route('deleteClassWiseFeeSetup', $row->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this classwise setup record?')">Delete</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No classwise setup configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        var rowsContainer = document.getElementById('setupRows');
        var classSelect = document.getElementById('classId');
        var loadedSetupInfo = document.getElementById('loadedSetupInfo');
        var baseTemplateRow = rowsContainer ? rowsContainer.querySelector('.setup-row') : null;
        var currentClassRows = [];
        if(!rowsContainer){
            return;
        }

        function makeRow(feeType, setupAmount){
            if(!baseTemplateRow){
                return null;
            }

            var row = baseTemplateRow.cloneNode(true);
            row.querySelectorAll('select, input').forEach(function(el){
                el.value = '';
            });

            var feeSelect = row.querySelector('select[name="feesType[]"]');
            var amountInput = row.querySelector('input[name="setupAmount[]"]');
            if(feeSelect && feeType){
                feeSelect.value = String(feeType);
            }
            if(amountInput && setupAmount){
                amountInput.value = setupAmount;
            }

            return row;
        }

        function clearAllRows(){
            rowsContainer.innerHTML = '';
        }

        function updateButtons(){
            var rows = rowsContainer.querySelectorAll('.setup-row');
            rows.forEach(function(row){
                var removeBtn = row.querySelector('.remove-setup-row');
                if(removeBtn){
                    removeBtn.disabled = rows.length === 1;
                }
            });
        }

        function setLoadInfo(message){
            if(!loadedSetupInfo){
                return;
            }

            if(!message){
                loadedSetupInfo.style.display = 'none';
                loadedSetupInfo.textContent = '';
                return;
            }

            loadedSetupInfo.textContent = message;
            loadedSetupInfo.style.display = '';
        }

        function applyClassSetupRows(rows){
            clearAllRows();

            if(rows.length > 0){
                rows.forEach(function(item){
                    var row = makeRow(item.fees_type_id, item.setup_amount);
                    if(row){
                        rowsContainer.appendChild(row);
                    }
                });
                setLoadInfo('Existing setup loaded automatically for this class. You can change amounts and save to update.');
            } else {
                var emptyRow = makeRow('', '');
                if(emptyRow){
                    rowsContainer.appendChild(emptyRow);
                }
                setLoadInfo('No existing setup found for this class. Create new setup and save.');
            }

            updateButtons();
        }

        function fetchClassRows(classId){
            var url = "{{ route('getClassWiseFeeSetupData') }}" + "?classId=" + encodeURIComponent(classId);
            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res){
                if(!res.ok){
                    throw new Error('Failed to load class setup');
                }
                return res.json();
            })
            .then(function(data){
                if(!data || !data.ok){
                    return [];
                }
                return Array.isArray(data.rows) ? data.rows : [];
            });
        }

        rowsContainer.addEventListener('click', function(e){
            if(e.target.classList.contains('add-setup-row')){
                var clone = makeRow('', '');
                if(!clone){
                    return;
                }
                rowsContainer.appendChild(clone);
                updateButtons();
            }

            if(e.target.classList.contains('remove-setup-row')){
                var rows = rowsContainer.querySelectorAll('.setup-row');
                if(rows.length <= 1){
                    return;
                }
                var row = e.target.closest('.setup-row');
                if(row){
                    row.remove();
                }
                updateButtons();
            }
        });

        rowsContainer.addEventListener('change', function(e){
            if(!e.target.matches('select[name="feesType[]"]')){
                return;
            }

            var classId = classSelect ? classSelect.value : '';
            if(!classId){
                return;
            }

            var selectedFee = e.target.value;
            if(!selectedFee){
                return;
            }

            var matched = currentClassRows.find(function(item){
                return String(item.fees_type_id) === String(selectedFee);
            });

            if(matched){
                var row = e.target.closest('.setup-row');
                var amountInput = row ? row.querySelector('input[name="setupAmount[]"]') : null;
                if(amountInput){
                    amountInput.value = matched.setup_amount;
                }
            }
        });

        function resetRowsForClass(classId){
            clearAllRows();
            if(!classId){
                var blank = makeRow('', '');
                if(blank){
                    rowsContainer.appendChild(blank);
                }
                setLoadInfo('');
                updateButtons();
                return;
            }

            applyClassSetupRows(classId);
        }

        if(classSelect){
            classSelect.addEventListener('change', function(){
                var classId = this.value;
                if(!classId){
                    currentClassRows = [];
                    resetRowsForClass('');
                    return;
                }

                setLoadInfo('Loading class formula...');
                fetchClassRows(classId)
                    .then(function(rows){
                        currentClassRows = rows;
                        applyClassSetupRows(rows);
                    })
                    .catch(function(){
                        currentClassRows = [];
                        resetRowsForClass('');
                        setLoadInfo('Failed to load class formula. Please try again.');
                    });
            });

            try{
                if(window.jQuery){
                    window.jQuery(classSelect).on('select2:select', function(){
                        classSelect.dispatchEvent(new Event('change'));
                    });
                }
            }catch(e){ }
        }

        var form = document.getElementById('classwiseSetupForm');
        if(form){
            form.addEventListener('submit', function(ev){
                var selected = [];
                var duplicate = false;
                form.querySelectorAll('select[name="feesType[]"]').forEach(function(sel){
                    var v = sel.value;
                    if(!v){
                        return;
                    }
                    if(selected.indexOf(v) !== -1){
                        duplicate = true;
                    } else {
                        selected.push(v);
                    }
                });

                if(duplicate){
                    ev.preventDefault();
                    alert('Duplicate fee types are not allowed for the same class setup submission.');
                }
            });
        }

        if(classSelect && classSelect.value){
            classSelect.dispatchEvent(new Event('change'));
        } else {
            currentClassRows = [];
            resetRowsForClass('');
        }
    })();
</script>
@endsection
