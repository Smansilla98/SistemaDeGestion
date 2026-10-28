@extends('layouts.app')

@section('title', 'Nuevo mensaje')

@section('content')
@include('community._head', ['icon' => 'bi-chat-dots', 'title' => 'Nuevo mensaje', 'back' => route('messages.index')])
<form method="POST" action="{{ route('messages.store') }}" class="card" style="max-width: 720px"><div class="card-body row g-3">
    @csrf
    <div class="col-12"><label class="form-label">Para</label>
        <select name="recipient_id" class="form-select" required><option value="">Elegí a quién</option>
            @foreach($people as $p)<option value="{{ $p['user']->id }}" @selected(old('recipient_id', $to) == $p['user']->id)>{{ $p['user']->name }} — {{ \App\Models\User::roleLabels()[$p['role']] ?? $p['role'] }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Asunto</label><input name="subject" class="form-control" value="{{ old('subject', $subject) }}"></div>
    <div class="col-12"><label class="form-label">Mensaje</label><textarea name="body" rows="6" class="form-control" required>{{ old('body') }}</textarea></div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-send"></i> Enviar</button></div>
</div></form>
@endsection
