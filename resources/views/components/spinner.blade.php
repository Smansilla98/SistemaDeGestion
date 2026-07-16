@props([
    'size' => 'md', // sm | md | lg
    'label' => null,
])

<span {{ $attributes->class(['cx-spinner', 'cx-spinner--'.$size]) }} role="status" aria-live="polite">
    <span class="cx-spinner__dot" aria-hidden="true"></span>
    @if($label)
        <span class="cx-spinner__label">{{ $label }}</span>
    @else
        <span class="visually-hidden">Cargando…</span>
    @endif
</span>
