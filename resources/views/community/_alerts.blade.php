@if($errors->any())
<div class="alert alert-danger">
    <strong>Revisá estos datos:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
@if(session('import_errors'))
<div class="alert alert-warning">
    <strong>Filas con problemas:</strong>
    <ul class="mb-0 small">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
