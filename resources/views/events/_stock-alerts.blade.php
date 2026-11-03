@if(count($stockAlerts) > 0)
<div class="alert alert-danger mb-4">
    <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Alertas de Stock para Eventos</h5>
    <p class="mb-3 fw-semibold">Los siguientes eventos requieren productos que no tienen suficiente stock:</p>
    <ul class="mb-0 alert-list">
        @foreach($stockAlerts as $alert)
        <li class="mb-2 pb-2 border-bottom border-danger border-opacity-25">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center gap-2">
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">{{ $alert['event']->name }}</strong>
                    <small class="text-muted d-block mb-1">{{ $alert['event']->formatted_date_time }}</small>
                    <strong class="d-block">{{ $alert['product']->name }}</strong>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger">
                        Stock: <strong>{{ $alert['current_stock'] }}</strong>
                    </span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">
                        Necesario: <strong>{{ $alert['expected_quantity'] }}</strong>
                    </span>
                    <span class="badge bg-danger text-white">
                        Faltan: <strong>{{ $alert['shortage'] }}</strong>
                    </span>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@endif
