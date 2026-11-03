@extends('layouts.app')

@section('title', 'Mensaje')

@section('content')
@include('community._head', ['icon' => 'bi-chat-dots', 'title' => $message->subject ?: 'Mensaje', 'back' => route('messages.index'),
    'subtitle' => 'De '.$message->sender?->name.' para '.$message->recipient?->name.' · '.$message->created_at->format('d/m/Y H:i')])
<div class="card" style="max-width: 720px"><div class="card-body">
    <p style="white-space: pre-line">{{ $message->body }}</p>
    @if($canSend && $message->recipient_id === auth()->id())
    <a href="{{ route('messages.create', ['para' => $message->sender_id, 'asunto' => 'Re: '.($message->subject ?: '')]) }}" class="btn btn-primary"><i class="bi bi-reply"></i> Responder</a>
    @endif
</div></div>
@endsection
