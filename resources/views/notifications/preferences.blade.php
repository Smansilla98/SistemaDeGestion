@extends('layouts.app')

@section('title', 'Preferencias de avisos')

@section('content')
@include('community._head', ['icon' => 'bi-sliders', 'title' => 'Preferencias de avisos', 'back' => route('notifications.index'),
    'subtitle' => 'Elegí qué avisos querés recibir en la app y cuáles también por correo.'])
<form method="POST" action="{{ route('notifications.preferences.save') }}" class="card" style="max-width: 720px">
    @csrf
    <table class="table mb-0 align-middle">
        <thead><tr><th>Tipo de aviso</th><th class="text-center">En la app</th><th class="text-center">Por correo</th></tr></thead>
        <tbody>
        @foreach($categories as $key => $label)
        <tr>
            <td>{{ $label }}</td>
            <td class="text-center"><input type="checkbox" class="form-check-input" name="in_app[{{ $key }}]" value="1" @checked($prefs[$key]['in_app'])></td>
            <td class="text-center"><input type="checkbox" class="form-check-input" name="email[{{ $key }}]" value="1" @checked($prefs[$key]['email'])></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-body"><button class="btn btn-primary">Guardar</button></div>
</form>
@endsection
