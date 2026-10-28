@extends('layouts.app')

@section('title', $campaign->title)

@section('content')
@include('community._head', ['icon' => 'bi-megaphone', 'title' => $campaign->title, 'back' => route('campaigns.index'),
    'subtitle' => $sent ? 'Enviada el '.$campaign->sent_at?->format('d/m/Y H:i').' a '.$campaign->recipients_count.' contactos.' : 'Borrador: revisá a quién le llega.'])
<div class="row g-4">
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <p style="white-space: pre-line">{{ $campaign->body }}</p>
        @if($campaign->link)<a href="{{ $campaign->link }}" target="_blank" rel="noopener">{{ $campaign->link }}</a>@endif
        @if(! $sent)
        <form method="POST" action="{{ route('campaigns.send', $campaign) }}" class="mt-3" onsubmit="return confirm('¿Enviar ahora? No se puede deshacer.')">@csrf
            <button class="btn btn-primary" @disabled($recipients->total() === 0)><i class="bi bi-send"></i> Enviar a {{ $recipients->total() }} contactos</button></form>
        @endif
    </div></div></div>
    <div class="col-lg-7"><div class="card">
        <div class="card-header"><h6 class="mb-0">{{ $sent ? 'Destinatarios' : 'Recibirían la comunicación' }}</h6></div>
        <ul class="list-group list-group-flush">
            @forelse($recipients as $r)
                @php $contact = $sent ? $r->contact : $r; @endphp
                <li class="list-group-item d-flex justify-content-between small">
                    <span>{{ $contact->name }}</span>
                    <span class="text-muted">@if($sent){{ $r->channels ?: 'sin canal (sólo registro)' }}@else{{ $contact->user_id ? 'app' : '' }}{{ $contact->email && $campaign->send_email ? ' email' : '' }}@endif
                        @if($contact->whatsappNumber()) <a href="https://wa.me/{{ $contact->whatsappNumber() }}?text={{ rawurlencode($campaign->title."\n\n".$campaign->body.($campaign->link ? "\n".$campaign->link : '')) }}" target="_blank" rel="noopener" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif</span>
                </li>
            @empty
                <li class="list-group-item text-muted">Ningún contacto cumple los filtros y aceptó comunicaciones.</li>
            @endforelse
        </ul>
        <div class="card-footer">{{ $recipients->links() }}</div>
    </div></div>
</div>
@endsection
