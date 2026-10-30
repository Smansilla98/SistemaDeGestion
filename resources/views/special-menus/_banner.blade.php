@php $activeMenu = auth()->user()?->restaurant_id ? app(\App\Services\SpecialMenuService::class)->activeFor((int) auth()->user()->restaurant_id) : null; @endphp
@if($activeMenu)
<div class="alert alert-info py-2 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-stars"></i>
    <span><strong>Menú especial: {{ $activeMenu->name }}</strong> · {{ $activeMenu->exclusive ? 'sólo se venden sus productos' : 'precios especiales en sus productos' }}@if($activeMenu->notes) · {{ \Illuminate\Support\Str::limit($activeMenu->notes, 120) }}@endif</span>
</div>
@endif
