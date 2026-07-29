<table class="signature-row">
    <tr>
        <td><div>Guardian</div><div class="signature-line"></div><div class="small">Signature</div></td>
        <td><div>Class Teacher</div><div class="signature-line"></div><div class="small">Signature</div></td>
        <td>
            <div>Principal/Head Master</div>
            @if(!empty($principalSignatureUrl))
                <img src="{{ $principalSignatureUrl }}" alt="Principal Signature" class="sign-image">
            @endif
            <div class="signature-line"></div>
            <div class="small">Signature</div>
        </td>
    </tr>
</table>
