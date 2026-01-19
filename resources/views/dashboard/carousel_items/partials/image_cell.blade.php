@if($url)
    <div class="symbol symbol-50px">
        <div class="symbol-label" style="background-image:url('{{ $url }}'); background-size:cover;"></div>
    </div>
@else
    <div class="symbol symbol-50px">
        <div class="symbol-label bg-light">
            <i class="ki-duotone ki-picture fs-2 text-muted"><span class="path1"></span><span class="path2"></span></i>
        </div>
    </div>
@endif