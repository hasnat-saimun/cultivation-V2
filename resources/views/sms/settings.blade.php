@extends('cultivation.include')

@section('backTitle') SMS Settings @endsection
@section('backIndex')
@php
    $demoHosts = ['demoadmin.cultivationapp.com', 'www.demoadmin.cultivationppa.com'];
    $isDemoHost = in_array(request()->getHost(), $demoHosts, true);
@endphp
@if($isDemoHost)
    <div class="alert alert-warning">SMS Settings are disabled on the demo environment.</div>
@else
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">SMS Settings</h5>
            </div>
            <div class="card-body">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                <form method="post" action="{{ route('sms.settings.save') }}">
        @csrf
        <div class="form-group">
            <label>Provider</label>
            <select id="provider-select" name="provider" class="form-control">
                <option value="http" {{ ($values['provider'] ?? '') === 'http' ? 'selected' : '' }}>ALPHA SMS</option>
                <option value="twilio" {{ ($values['provider'] ?? '') === 'twilio' ? 'selected' : '' }}>Twilio</option>
            </select>
        </div>

        <div class="form-check">
            <input type="checkbox" id="sms-settings-enabled" name="sms_settings_enabled" value="1" class="form-check-input" {{ ($smsEnabled ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Enable SMS Sending</label>
        </div>

        <div class="form-check">
            <input type="checkbox" name="sms_on_present" value="1" class="form-check-input" {{ ($values['sms_on_present'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Send SMS on Present</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="sms_on_absent" value="1" class="form-check-input" {{ ($values['sms_on_absent'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Send SMS on Absent</label>
        </div>

        <div class="form-group">
            <label>Present message template</label>
            <textarea name="sms_message_present" class="form-control" rows="2">{{ $values['sms_message_present'] ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label>Absent message template</label>
            <textarea name="sms_message_absent" class="form-control" rows="2">{{ $values['sms_message_absent'] ?? '' }}</textarea>
        </div>

        <hr>
        <div id="provider-http" class="provider-section">
            <h4>ALPHA SMS Provider</h4>
            <div class="form-group">
                <label>API URL</label>
                <input name="api_url" class="form-control" value="{{ $values['api_url'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>API Key</label>
                <input name="api_key" class="form-control" value="{{ $values['api_key'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>Alpha Rate URL (for live plans)</label>
                <input name="alpha_rate_url" class="form-control" value="{{ $values['alpha_rate_url'] ?? config('sms.alpha_rate_url') }}" placeholder="https://portal.sms.net.bd/sms_rate">
            </div>
            <div class="form-group">
                <label>Sender</label>
                <input name="sender" class="form-control" value="{{ $values['sender'] ?? '' }}">
            </div>

            <div class="form-group">
                <label>HTTP Provider Parameter Mapping (JSON)</label>
                <small class="form-text text-muted">Map request parameter names to templates. Use placeholders: {to}, {message}, {api_key}, {sender}. Example: { "msisdn": "{to}", "sms": "{message}", "api_key":"{api_key}" }</small>
                @php
                    $httpMap = $values['http_param_map'] ?? config('sms.http_param_map');
                    if (is_array($httpMap)) {
                        $httpMap = json_encode($httpMap, JSON_PRETTY_PRINT);
                    }
                @endphp
                <textarea name="http_param_map" class="form-control" rows="3">{{ $httpMap }}</textarea>
            </div>
        </div>

        <hr>
        <div id="provider-twilio" class="provider-section">
            <h4>Twilio</h4>
            <div class="form-group">
                <label>Account SID</label>
                <input name="twilio_account_sid" class="form-control" value="{{ $values['twilio_account_sid'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>Auth Token</label>
                <input name="twilio_auth_token" class="form-control" value="{{ $values['twilio_auth_token'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>From (Twilio number)</label>
                <input name="twilio_from" class="form-control" value="{{ $values['twilio_from'] ?? '' }}">
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save</button>
                </form>

                <hr>
                <h4>Send Test SMS</h4>
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                <form method="post" action="{{ route('sms.settings.test') }}">
                    @csrf
                    <div class="form-group">
                        <label>Phone number</label>
                        <input name="phone" class="form-control" placeholder="e.g. +15551234567" required>
                    </div>
                    <div class="form-group">
                        <label>Message (optional)</label>
                        <textarea name="message" class="form-control" rows="2">{{ $values['sms_message_absent'] ?? '' }}</textarea>
                    </div>
                    <button class="btn btn-secondary" type="submit">Send Test SMS</button>
                </form>

                <hr>
                <div class="mb-3">
                    <button id="sms-rate-toggle" class="btn btn-info">SMS Rate</button>
                </div>
                @php
                    $ratesRaw = $values['rates'] ?? config('sms.rates', []);
                    if (is_string($ratesRaw)) {
                        $rates = @json_decode($ratesRaw, true) ?: [];
                    } else {
                        $rates = is_array($ratesRaw) ? $ratesRaw : [];
                    }
                @endphp
                <div id="sms-rate" style="display:none;">
                    <h4>SMS Rate</h4>
                    @if(empty($rates))
                        <div class="alert alert-secondary">No rate information configured.</div>
                    @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Provider</th>
                                                <th>Per SMS</th>
                                                <th>Currency</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sms-rate-body">
                                            {{-- initial fallback: show configured ALPHA/http rate if present --}}
                                            @php $alphaRate = $rates['http'] ?? $rates['alpha'] ?? null; @endphp
                                            @if($alphaRate)
                                                <tr>
                                                    <td>ALPHA SMS</td>
                                                    <td>{{ $alphaRate['per_sms'] ?? '' }}</td>
                                                    <td>{{ $alphaRate['currency'] ?? '' }}</td>
                                                    <td>{{ $alphaRate['note'] ?? '' }}</td>
                                                </tr>
                                            @else
                                                <tr id="sms-rate-loading"><td colspan="4">No configured rate. Click <strong>SMS Rate</strong> to fetch live Alpha SMS plans.</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function(){
        function toggleProviderSections(){
            var sel = document.getElementById('provider-select');
            if(!sel) return;
            var v = sel.value;
            document.querySelectorAll('.provider-section').forEach(function(el){ el.style.display = 'none'; });
            var node = document.getElementById('provider-' + v);
            if(node) node.style.display = '';
        }
        document.addEventListener('DOMContentLoaded', function(){
            var sel = document.getElementById('provider-select');
            if(!sel) return;
            sel.addEventListener('change', toggleProviderSections, true);
            toggleProviderSections();
            var enabledInput = document.getElementById('sms-settings-enabled');
            if (enabledInput) {
                var toggleUrl = @json(route('sms.settings.toggle'));
                var csrfToken = @json(csrf_token());
                var saving = false;
                try { localStorage.setItem('sms_settings_enabled', enabledInput.checked ? '1' : '0'); } catch (e) {}
                enabledInput.addEventListener('change', function(){
                    if (saving) return;
                    var enabled = enabledInput.checked;
                    try { localStorage.setItem('sms_settings_enabled', enabled ? '1' : '0'); } catch (e) {}
                    saving = true;
                    fetch(toggleUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ enabled: enabled ? 1 : 0 })
                    })
                        .then(function(r){ return r.json(); })
                        .then(function(j){
                            if (!j || !j.ok) {
                                enabledInput.checked = !enabled;
                                try { localStorage.setItem('sms_settings_enabled', enabledInput.checked ? '1' : '0'); } catch (e) {}
                            }
                        })
                        .catch(function(){
                            enabledInput.checked = !enabled;
                            try { localStorage.setItem('sms_settings_enabled', enabledInput.checked ? '1' : '0'); } catch (e) {}
                        })
                        .finally(function(){ saving = false; });
                });
            }
            var rateToggle = document.getElementById('sms-rate-toggle');
            if(rateToggle){
                rateToggle.addEventListener('click', function(e){
                    e.preventDefault();
                    var box = document.getElementById('sms-rate');
                    if(!box) return;
                    var willShow = (box.style.display === 'none' || box.style.display === '');
                    box.style.display = willShow ? 'block' : 'none';
                    if (willShow) {
                        // fetch live Alpha SMS rates
                        var url = @json(route('sms.alphaRate'));
                        fetch(url, { credentials: 'same-origin' })
                            .then(function(r){
                                return r.text().then(function(t){
                                    return {
                                        ok: r.ok,
                                        status: r.status,
                                        text: t,
                                        contentType: r.headers.get('content-type') || ''
                                    };
                                });
                            })
                            .then(function(res){
                                var j = null;
                                if (res.contentType.indexOf('application/json') !== -1 || /^[\s\[]?\{/.test(res.text)) {
                                    try { j = JSON.parse(res.text); } catch (e) { j = null; }
                                }
                                var body = document.getElementById('sms-rate-body');
                                if(!body) return;
                                body.innerHTML = '';
                                if (j && j.ok && j.data) {
                                    var rows = [];

                                    function mkRow(price, cur, note) {
                                        price = price || '';
                                        cur = cur || '';
                                        note = note || '';
                                        return '<tr><td>ALPHA SMS</td><td>'+price+'</td><td>'+cur+'</td><td>'+note+'</td></tr>';
                                    }

                                    // helpers moved to top-level so they're available where needed
                                    function isTextualRaw(s) {
                                        if (!s) return false;
                                        s = (''+s).trim();
                                        if (/year|month|carry forward|carryforward|sign up|signup|validity/i.test(s)) return true;
                                        if (/[A-Za-z()]/.test(s)) return true;
                                        return false;
                                    }

                                    function formatNumberStr(n) {
                                        if (!n) return '';
                                        var s = (''+n).trim();
                                        if (s === '') return '';
                                        var parts = s.split('.');
                                        var intPart = parts[0];
                                        var dec = parts.length>1 ? '.'+parts[1] : '';
                                        intPart = intPart.replace(/^0+(\d)/, '$1');
                                        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        return intPart + dec;
                                    }

                                    // try to normalize an item that might be a JSON-like string
                                    function tryParseJsonLike(str) {
                                        if (typeof str !== 'string') return null;
                                        str = str.trim();
                                        if (!str) return null;
                                        try {
                                            return JSON.parse(str);
                                        } catch(e) {
                                            // try to fix common issues: replace fancy quotes
                                            var s2 = str.replace(/[“”‘’]/g, '"');
                                            try { return JSON.parse(s2); } catch(e2) { return null; }
                                        }

                                            
                                    }

                                    // If server returned a raw HTML/string body
                                    if (typeof j.data === 'string') {
                                        var preview = j.data.replace(/<[^>]+>/g,'').trim();
                                        if (preview.length > 300) preview = preview.substring(0,300) + '...';
                                        var linkTo = j.url || @json(config('sms.alpha_rate_url'));
                                        rows.push('<tr><td colspan="4">Response is HTML/Plain text. <a href="'+linkTo+'" target="_blank" rel="noopener">Open pricing page</a><div style="margin-top:6px;color:#666;">'+preview+'</div></td></tr>');
                                    }

                                    // If array: iterate items, handle strings, objects, or pre-expanded rows
                                    if (Array.isArray(j.data)) {
                                        j.data.forEach(function(item){
                                            if (item === null || item === undefined) return;
                                            if (typeof item === 'string') {
                                                // maybe JSON-like string containing a mapping -> try parse
                                                var parsed = tryParseJsonLike(item);
                                                if (parsed && typeof parsed === 'object') {
                                                    // if parsed is an array of objects
                                                    if (Array.isArray(parsed)) {
                                                        parsed.forEach(function(p){
                                                            var raw = p.raw_value || p.raw || '';
                                                            var price = p.per_sms || p.price || p.rate || '';
                                                            var cur = p.currency || '';
                                                            var note = p.name || p.Details || JSON.stringify(p);
                                                            var display = '';
                                                            if (isTextualRaw(raw)) {
                                                                display = raw;
                                                                if (!cur && raw.indexOf('৳')!==-1) cur = 'BDT';
                                                            } else {
                                                                var num = (price && price+'') || (raw+'');
                                                                num = (''+num).replace(/[^0-9\.,\-]/g,'');
                                                                num = num.replace(/,/g,'');
                                                                display = formatNumberStr(num);
                                                            }
                                                            rows.push(mkRow(display, cur, note));
                                                        });
                                                    } else {
                                                        // object with Details and tier keys
                                                        var cat = parsed.Details || '';
                                                        Object.keys(parsed).forEach(function(k){
                                                            if (k === 'Details') return;
                                                            var v = parsed[k];
                                                            if (v === null || v === '') return;
                                                            var priceRaw = (''+v).trim();
                                                            var cur = priceRaw.indexOf('৳') !== -1 ? 'BDT' : '';
                                                            var num = priceRaw.replace(/[^0-9\.,\-]/g, '');
                                                            num = num.replace(/,/g,'');
                                                            var name = k;
                                                            if (cat) name = cat + ' - ' + k;
                                                            var display = isTextualRaw(priceRaw) ? priceRaw : formatNumberStr(num);
                                                            rows.push(mkRow(display, cur, name));
                                                        });
                                                    }
                                                } else {
                                                    // unknown string: show as note
                                                    rows.push(mkRow('', '', item));
                                                }
                                            } else if (typeof item === 'object') {
                                                // already structured object
                                                // if object looks like a tier mapping (Details + tiers), expand
                                                if ('Details' in item) {
                                                    var cat = item.Details || '';
                                                    Object.keys(item).forEach(function(k){
                                                        if (k === 'Details') return;
                                                        var v = item[k];
                                                        if (v === null || v === '') return;
                                                        var priceRaw = (''+v).trim();
                                                        var cur = priceRaw.indexOf('৳') !== -1 ? 'BDT' : '';
                                                        var num = priceRaw.replace(/[^0-9\.,\-]/g, '');
                                                        num = num.replace(/,/g,'');
                                                        var name = k;
                                                        if (cat) name = cat + ' - ' + k;
                                                        var display = isTextualRaw(priceRaw) ? priceRaw : formatNumberStr(num);
                                                        rows.push(mkRow(display, cur, name));
                                                    });
                                                } else {
                                                    var raw = item.raw_value || item.raw || '';
                                                    var price = item.per_sms || item.price || item.rate || item.amount || '';
                                                    var cur = item.currency || item.currency_code || '';
                                                    var note = item.name || item.note || JSON.stringify(item);
                                                    var display = '';
                                                    if (isTextualRaw(raw)) {
                                                        display = raw;
                                                        if (!cur && raw.indexOf('৳')!==-1) cur = 'BDT';
                                                    } else {
                                                        var num = (price && price+'') || (raw+'');
                                                        num = (''+num).replace(/[^0-9\.,\-]/g,'');
                                                        num = num.replace(/,/g,'');
                                                        display = formatNumberStr(num);
                                                    }
                                                    rows.push(mkRow(display, cur, note));
                                                }
                                            } else {
                                                rows.push(mkRow('', '', JSON.stringify(item)));
                                            }
                                        });
                                    }

                                    // If object: try to handle plans array or single object
                                    if (!Array.isArray(j.data) && typeof j.data === 'object') {
                                        if (j.data.plans && Array.isArray(j.data.plans)) {
                                            j.data.plans.forEach(function(p){
                                                var price = p.price || p.per_sms || p.rate || p.amount || '';
                                                var cur = p.currency || p.currency_code || '';
                                                var note = p.name || p.note || JSON.stringify(p);
                                                rows.push(mkRow(price, cur, note));
                                            });
                                        } else if ('Details' in j.data) {
                                            var cat = j.data.Details || '';
                                            Object.keys(j.data).forEach(function(k){
                                                if (k === 'Details') return;
                                                var v = j.data[k];
                                                if (v === null || v === '') return;
                                                var priceRaw = (''+v).trim();
                                                var cur = priceRaw.indexOf('৳') !== -1 ? 'BDT' : '';
                                                var num = priceRaw.replace(/[^0-9\.,\-]/g, '');
                                                num = num.replace(/,/g,'');
                                                var name = k;
                                                if (cat) name = cat + ' - ' + k;
                                                rows.push(mkRow(num, cur, name));
                                            });
                                        } else {
                                            var price = j.data.price || j.data.per_sms || j.data.rate || j.data.amount || '';
                                            var cur = j.data.currency || j.data.currency_code || '';
                                            var note = j.data.note || j.data.name || JSON.stringify(j.data);
                                            rows.push(mkRow(price, cur, note));
                                        }
                                    }

                                    if (rows.length === 0) rows.push('<tr><td colspan="4">No plan data returned.</td></tr>');
                                    body.innerHTML = rows.join('\n');

                                    // Try to convert flat rows (Category - Tier) into a matrix like the provider pricing page.
                                    try {
                                        var table = body.closest('table');
                                        if (!table) throw new Error('table element not found');

                                        // Build structured data from j.data when possible (prefer the parsed objects)
                                        var structuredItems = [];
                                        if (Array.isArray(j.data)) {
                                            j.data.forEach(function(it){
                                                if (!it) return;
                                                if (typeof it === 'object') {
                                                    // prefer normalized fields
                                                    var name = it.name || it.note || it.name || '';
                                                    var raw = it.raw_value || it.raw || '';
                                                    var per = it.per_sms || it.price || it.rate || it.amount || '';
                                                    // if name contains ' - ', treat as Category - Tier
                                                    if (!name && it.Details && (Object.keys(it).length>1)) {
                                                        // if object is a tier mapping, convert each tier to an item
                                                        Object.keys(it).forEach(function(k){
                                                            if (k === 'Details') return;
                                                            var v = it[k];
                                                            structuredItems.push({ name: (it.Details || '') + ' - ' + k, raw_value: (''+v), per_sms: '', currency: ((''+v).indexOf('৳')!==-1 ? 'BDT':'' ) });
                                                        });
                                                    } else if (typeof name === 'string' && name.indexOf(' - ') !== -1) {
                                                        structuredItems.push({ name: name, raw_value: raw || per || '', per_sms: per, currency: it.currency || it.currency_code || '' });
                                                    }
                                                }
                                            });
                                        }

                                        // Fallback: try to read from the flat DOM rows if structuredItems is empty
                                        if (structuredItems.length === 0) {
                                            var flat = [];
                                            body.querySelectorAll('tr').forEach(function(tr){
                                                var tds = tr.querySelectorAll('td');
                                                if (tds.length >= 4) {
                                                    flat.push({provider: tds[0].textContent.trim(), per_sms: tds[1].textContent.trim(), currency: tds[2].textContent.trim(), note: tds[3].textContent.trim()});
                                                }
                                            });
                                            flat.forEach(function(it){ if (it.note && it.note.indexOf(' - ')!==-1) structuredItems.push({ name: it.note, raw_value: it.per_sms, per_sms: it.per_sms, currency: it.currency }); });
                                        }

                                        if (structuredItems.length === 0) throw new Error('no structured tier items');

                                        // Build tiers order and category map
                                        var tiersOrder = [];
                                        var categories = {};
                                        structuredItems.forEach(function(it){
                                            var parts = it.name.split(' - ');
                                            if (parts.length < 2) return;
                                            var cat = parts[0].trim();
                                            var tier = parts.slice(1).join(' - ').trim();
                                            if (tiersOrder.indexOf(tier) === -1) tiersOrder.push(tier);
                                            categories[cat] = categories[cat] || {};
                                            categories[cat][tier] = it;
                                        });

                                        if (tiersOrder.length === 0 || Object.keys(categories).length === 0) throw new Error('insufficient matrix data');

                                        // build matrix table HTML (professional look)
                                        var thead = '<thead class="thead-light"><tr><th style="min-width:160px"></th>';
                                        tiersOrder.forEach(function(t){ thead += '<th class="text-center">'+t+'</th>'; });
                                        thead += '</tr></thead>';

                                        var tbodyHtml = '<tbody>';
                                        Object.keys(categories).forEach(function(cat){
                                            tbodyHtml += '<tr><th class="font-weight-normal">'+cat+'</th>';
                                            tiersOrder.forEach(function(t){
                                                var cell = categories[cat][t];
                                                if (!cell) {
                                                    tbodyHtml += '<td class="text-center"></td>';
                                                } else {
                                                    var rawv = cell.raw_value || '';
                                                    var per = cell.per_sms || '';
                                                    var display = '';
                                                    if (isTextualRaw(rawv)) {
                                                        display = rawv;
                                                    } else {
                                                        var num = (per ? (''+per) : (rawv+''));
                                                        num = num.replace(/[^0-9\.\,\-]/g,'');
                                                        num = num.replace(/,/g,'');
                                                        if (num === '') display = rawv || per || '';
                                                        else {
                                                            // if num has decimal part keep two decimals
                                                            if (num.indexOf('.') !== -1) {
                                                                var f = parseFloat(num);
                                                                display = '৳' + f.toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                            } else {
                                                                display = '৳' + Number(num).toLocaleString();
                                                            }
                                                        }
                                                    }
                                                    tbodyHtml += '<td class="text-center align-middle">'+(display||'')+'</td>';
                                                }
                                            });
                                            tbodyHtml += '</tr>';
                                        });
                                        tbodyHtml += '</tbody>';

                                        table.innerHTML = thead + tbodyHtml;
                                        table.classList.add('table-striped','table-hover');
                                    } catch (e) { console.warn('rate matrix render failed', e); }
                                } else {
                                    var msg = 'Failed to fetch live rates.';
                                    if (j && (j.message || j.status)) {
                                        msg = 'Failed to fetch live rates: ' + (j.message || j.status);
                                    } else if (res && res.text && res.text.indexOf('<') !== -1) {
                                        msg = 'Session expired or server returned HTML. Please reload and try again.';
                                    }
                                    body.innerHTML = '<tr><td colspan="4">'+msg+'</td></tr>';
                                }
                            }).catch(function(err){
                                var body = document.getElementById('sms-rate-body');
                                if(body) body.innerHTML = '<tr><td colspan="4">Error fetching rates: '+err.message+'</td></tr>';
                            });
                    }
                }, true);
            }
        });
    })();
</script>
@endpush

@endif
@endsection
