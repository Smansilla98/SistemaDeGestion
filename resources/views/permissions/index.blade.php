@extends('layouts.app')

@section('title', 'Permisos por Rol y Usuario')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2rem;">
            <i class="bi bi-shield-lock"></i> Gestionar Permisos
        </h1>
        <p class="text-white opacity-90">Configura qué vistas y acciones tiene cada rol y cada usuario.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

<ul class="nav nav-tabs nav-tabs-card mb-4" id="permissionsTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'roles' ? 'active' : '' }}" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-pane" type="button" role="tab">
            <i class="bi bi-people me-1"></i> Por rol
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'users' ? 'active' : '' }}" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-pane" type="button" role="tab">
            <i class="bi bi-person me-1"></i> Por usuario
        </button>
    </li>
</ul>

<div class="tab-content" id="permissionsTabContent">
    {{-- Pestaña Por rol --}}
    <div class="tab-pane fade {{ $tab === 'roles' ? 'show active' : '' }}" id="roles-pane" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-people"></i> Permisos por rol</h5>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 me-2 text-nowrap">Rol:</label>
                    @php $permDefaultRole = in_array('ADMIN', $roles, true) ? 'ADMIN' : ($roles[0] ?? 'ADMIN'); @endphp
                    <select class="form-select form-select-sm w-auto" id="roleSelect" style="min-width: 180px;">
                        @foreach($roles as $r)
                            <option value="{{ $r }}" {{ $r === $permDefaultRole ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Módulo</th>
                                @foreach($actionLabels as $actionKey => $label)
                                    <th class="text-center" style="min-width: 90px;">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $idx = 1; @endphp
                            @foreach($modules as $module)
                                <tr>
                                    <td>{{ $idx++ }}</td>
                                    <td><strong>{{ $module['label'] }}</strong></td>
                                    @foreach($actionLabels as $actionKey => $label)
                                        @php
                                            $permissionKey = $module['key'] . '.' . $actionKey;
                                            $show = in_array($actionKey, $module['actions'] ?? [], true);
                                            $roleValues = [];
                                            foreach ($roles as $r) {
                                                $roleValues[$r] = $matrixByRole[$permissionKey][$r] ?? false;
                                            }
                                        @endphp
                                        <td class="text-center">
                                            @if($show)
                                                @foreach($roles as $r)
                                                    <div class="form-check form-switch d-inline-block justify-content-center role-cell" data-role="{{ $r }}">
                                                        <input class="form-check-input" type="checkbox" role="switch"
                                                            {{ ($matrixByRole[$permissionKey][$r] ?? false) ? 'checked' : '' }}
                                                            data-role="{{ $r }}" data-key="{{ $permissionKey }}">
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-success" id="saveRoleBtn">
                    <i class="bi bi-check-lg"></i> Guardar cambios del rol
                </button>
            </div>
        </div>

        <form id="formRolePermissions" method="POST" action="{{ route('permissions.update-role') }}" class="d-none">
            @csrf
            <input type="hidden" name="role" id="formRoleName">
            <div id="formRolePermissionsInputs"></div>
        </form>
    </div>

    {{-- Pestaña Por usuario --}}
    <div class="tab-pane fade {{ $tab === 'users' ? 'show active' : '' }}" id="users-pane" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-person"></i> Permisos por usuario</h5>
                <input type="text" class="form-control form-control-sm" id="userSearch" placeholder="Buscar usuario..." style="max-width: 280px;">
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Selecciona un usuario para editar sus permisos. Los valores personalizados sobrescriben los del rol.</p>
                <div class="row g-3" id="usersList">
                    @foreach($users as $u)
                        <div class="col-12 col-md-6 col-lg-4 user-card" data-name="{{ strtolower($u->name) }}" data-username="{{ strtolower($u->username ?? '') }}" data-role="{{ strtolower($u->role) }}">
                            <div class="card border h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $u->name }}</strong>
                                        <div class="small text-muted">{{ $u->username }} · {{ $u->role }}</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-edit-user" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}" data-user-role="{{ $u->role }}">
                                        <i class="bi bi-pencil-square"></i> Permisos
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($users->isEmpty())
                    <p class="text-muted text-center py-4">No hay usuarios en este restaurante.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal permisos de usuario --}}
<div class="modal fade" id="userPermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-gear"></i> Permisos: <span id="modalUserName"></span> <span class="badge bg-secondary ms-2" id="modalUserRole"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUserPermissions">
                    <input type="hidden" name="user_id" id="formUserId">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Módulo</th>
                                    @foreach($actionLabels as $actionKey => $label)
                                        <th class="text-center" style="min-width: 90px;">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody id="userPermissionsTableBody">
                                {{-- Se llena por JS --}}
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x"></i> Salir</button>
                <button type="button" class="btn btn-success" id="saveUserPermissionsBtn"><i class="bi bi-check-lg"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.nav-tabs-card .nav-link {
    border: none;
    color: rgba(255,255,255,0.85);
    font-weight: 600;
    padding: 0.75rem 1.25rem;
    border-radius: 12px 12px 0 0;
}
.nav-tabs-card .nav-link:hover { color: white; }
.nav-tabs-card .nav-link.active {
    background: white;
    color: var(--conurbania-primary);
    border-bottom: 2px solid white;
}
.role-cell { display: none !important; }
.role-cell.visible { display: inline-flex !important; }
.form-check-input { min-width: 2.8rem; min-height: 1.2rem; cursor: pointer; }
.user-card:hover { transform: translateY(-2px); transition: transform 0.2s ease; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const roles = @json($roles);
    const modules = @json($modules);
    const actionLabels = @json($actionLabels);
    const matrixByRole = @json($matrixByRole);

    // --- Pestaña Por rol: mostrar solo toggles del rol seleccionado
    const roleSelect = document.getElementById('roleSelect');
    const roleCells = document.querySelectorAll('.role-cell');

    function showRoleColumn(role) {
        document.querySelectorAll('.role-cell').forEach(function(cell) {
            cell.classList.toggle('visible', cell.dataset.role === role);
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            showRoleColumn(this.value);
        });
        showRoleColumn(roleSelect.value);
    }

    // Guardar permisos del rol (solo los del rol seleccionado)
    document.getElementById('saveRoleBtn').addEventListener('click', function() {
        const role = roleSelect.value;
        const form = document.getElementById('formRolePermissions');
        form.querySelector('#formRoleName').value = role;
        const container = form.querySelector('#formRolePermissionsInputs');
        container.innerHTML = '';
        document.querySelectorAll('.role-cell.visible input').forEach(function(inp) {
            const key = inp.dataset.key;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'permissions[' + key + ']';
            hidden.value = inp.checked ? '1' : '0';
            container.appendChild(hidden);
        });
        form.submit();
    });

    // --- Pestaña Por usuario: búsqueda
    document.getElementById('userSearch').addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.user-card').forEach(function(card) {
            const name = card.dataset.name || '';
            const username = card.dataset.username || '';
            const role = card.dataset.role || '';
            const show = !term || name.includes(term) || username.includes(term) || role.includes(term);
            card.style.display = show ? '' : 'none';
        });
    });

    // Abrir modal y cargar matriz del usuario
    const modal = new bootstrap.Modal(document.getElementById('userPermissionsModal'));
    const modalUserName = document.getElementById('modalUserName');
    const modalUserRole = document.getElementById('modalUserRole');
    const formUserId = document.getElementById('formUserId');
    const tbody = document.getElementById('userPermissionsTableBody');

    document.querySelectorAll('.btn-edit-user').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const userRole = this.dataset.userRole;
            formUserId.value = userId;
            modalUserName.textContent = userName;
            modalUserRole.textContent = userRole;

            fetch('{{ url("permissions/user") }}/' + userId + '/matrix', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                const matrix = data.matrix || {};
                let html = '';
                let idx = 1;
                modules.forEach(function(module) {
                    html += '<tr><td>' + idx++ + '</td><td><strong>' + module.label + '</strong></td>';
                    Object.keys(actionLabels).forEach(function(actionKey) {
                        const permissionKey = module.key + '.' + actionKey;
                        const hasAction = (module.actions || []).indexOf(actionKey) !== -1;
                        if (hasAction) {
                            const checked = matrix[permissionKey] ? ' checked' : '';
                            html += '<td class="text-center"><div class="form-check form-switch justify-content-center d-flex"><input class="form-check-input" type="checkbox" role="switch" name="permissions[' + permissionKey + ']" value="1"' + checked + '></div></td>';
                        } else {
                            html += '<td class="text-center text-muted">—</td>';
                        }
                    });
                    html += '</tr>';
                });
                tbody.innerHTML = html;
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="' + (2 + Object.keys(actionLabels).length) + '" class="text-center text-danger">Error al cargar permisos.</td></tr>';
            });
            modal.show();
        });
    });

    document.getElementById('saveUserPermissionsBtn').addEventListener('click', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("permissions.update-user") }}';
        form.innerHTML = '{{ csrf_field() }}<input type="hidden" name="user_id" value="">';
        form.querySelector('input[name="user_id"]').value = formUserId.value;
        document.querySelectorAll('#userPermissionsTableBody input[type="checkbox"]').forEach(function(inp) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = inp.name;
            h.value = inp.checked ? '1' : '0';
            form.appendChild(h);
        });
        document.body.appendChild(form);
        form.submit();
    });
})();
</script>
@endpush
