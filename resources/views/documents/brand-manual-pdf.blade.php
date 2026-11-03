<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Manual de marca — {{ $entrepreneur->business_name }}</title>
<style>
    @page { margin: 48px 52px; }
    body { font-family: DejaVu Sans, sans-serif; color: #1f2328; font-size: 12px; line-height: 1.55; }
    h1 { font-size: 30px; margin: 0 0 6px; }
    h2 { font-size: 16px; margin: 28px 0 10px; padding-bottom: 6px; border-bottom: 2px solid {{ $colors[0] ?? '#333' }}; }
    .tag { font-size: 14px; color: #555; font-style: italic; }
    .cover { padding: 40px 0 30px; border-bottom: 6px solid {{ $colors[0] ?? '#333' }}; margin-bottom: 10px; }
    .logo { max-width: 220px; max-height: 120px; margin-bottom: 18px; }
    table.colors td { padding: 0 14px 10px 0; vertical-align: top; text-align: center; font-size: 10px; }
    .sw { width: 70px; height: 70px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 4px; }
    .muted { color: #777; }
    .foot { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 9px; color: #999; text-align: center; }
</style>
</head>
<body>
    <div class="foot">Manual de marca · {{ $entrepreneur->business_name }} · generado el {{ now()->format('d/m/Y') }}</div>
    <div class="cover">
        @if($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif
        <h1>{{ $entrepreneur->business_name }}</h1>
        @if(! empty($brand['tagline']))<div class="tag">{{ $brand['tagline'] }}</div>@endif
    </div>

    @if($entrepreneur->description)
    <h2>Quiénes somos</h2>
    <p>{{ $entrepreneur->description }}</p>
    @endif

    @if(! empty($brand['values']))
    <h2>Lo que nos identifica</h2>
    <p style="white-space: pre-line">{{ $brand['values'] }}</p>
    @endif

    <h2>Paleta de colores</h2>
    @if($colors)
    <table class="colors"><tr>
        @foreach($colors as $c)<td><div class="sw" style="background: {{ $c }}"></div>{{ strtoupper($c) }}<br><span class="muted">RGB {{ implode(', ', sscanf($c, '#%02x%02x%02x')) }}</span></td>@endforeach
    </tr></table>
    @else
    <p class="muted">Todavía no se definieron colores.</p>
    @endif

    <h2>Tipografías</h2>
    <p><strong>Principal:</strong> {{ $brand['font_primary'] ?? 'sin definir' }}<br>
       <strong>Secundaria:</strong> {{ $brand['font_secondary'] ?? 'sin definir' }}</p>

    @if(! empty($brand['usage']))
    <h2>Uso del logo</h2>
    <p style="white-space: pre-line">{{ $brand['usage'] }}</p>
    @endif

    @if($entrepreneur->payment_alias || $entrepreneur->contact)
    <h2>Contacto</h2>
    <p>@if($entrepreneur->contact){{ $entrepreneur->contact->name }}@if($entrepreneur->contact->phone) · {{ $entrepreneur->contact->phone }}@endif @if($entrepreneur->contact->email) · {{ $entrepreneur->contact->email }}@endif<br>@endif
       @if($entrepreneur->payment_alias)Alias para pagos: {{ $entrepreneur->payment_alias }}@endif</p>
    @endif
</body>
</html>
