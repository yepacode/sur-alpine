@extends('panel.layout')

@section('titulo', 'Usuarios')

@section('contenido')
    <h1 class="text-2xl font-bold tracking-tight">Usuarios</h1>
    {{-- Con dos roles, hablar de «una escalera» no explicaba nada. --}}
    <p class="mt-1 text-sm text-tinta-500">
        <strong class="font-semibold text-tinta-700">Cliente</strong>: sólo su cuenta, sus carros y sus cotizaciones.
        <strong class="font-semibold text-tinta-700">Administrador</strong>: además, todo este panel.
    </p>

    {{-- Buscador y filtro. Casi todas las filas son clientes del sitio, no
         gente del equipo, y sin esto había que pasar página por página. --}}
    <form method="get" class="mt-6 flex flex-wrap items-end gap-2">
        <div>
            <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Buscar</label>
            <input id="q" type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Nombre o correo"
                   class="w-56 rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
        </div>
        <div>
            <label for="rol" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Rol</label>
            <select id="rol" name="rol" class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach ($roles as $valor => $texto)
                    <option value="{{ $valor }}" @selected($filtros['rol'] === (string) $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-marca-700 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-800">Filtrar</button>
        @if ($filtros['q'] !== '' || $filtros['rol'] !== '')
            <a href="{{ route('panel.usuarios') }}" class="px-2 py-2 text-sm font-medium text-tinta-500 underline-offset-2 hover:underline">Ver todos</a>
        @endif
    </form>

    @if ($errors->any())
        <div role="alert" class="mt-6 max-w-2xl rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 overflow-x-auto rounded-xl border border-tinta-200 bg-white">
        <table class="w-full min-w-2xl text-sm">
            <thead class="border-b border-tinta-200 text-left text-xs uppercase tracking-wide text-tinta-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium">Correo</th>
                    <th class="px-4 py-3 font-medium">Rol</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta-200">
                @foreach ($usuarios as $usuario)
                    <tr @class(['hover:bg-tinta-50', 'bg-marca-50' => $editando?->is($usuario)])>
                        <td class="px-4 py-3 font-medium">
                            {{ $usuario->name }}
                            @if ($usuario->is(auth()->user()))
                                <span class="ml-1 text-xs text-tinta-400">(tú)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-tinta-600">{{ $usuario->email }}</td>
                        <td class="px-4 py-3">{{ $usuario->rol->etiqueta() }}</td>
                        <td class="px-4 py-3">
                            @if ($usuario->activo)
                                <span class="rounded-full bg-marca-100 px-2 py-0.5 text-xs font-medium text-marca-700">Activo</span>
                            @else
                                <span class="rounded-full bg-tinta-100 px-2 py-0.5 text-xs font-medium text-tinta-500">Desactivado</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.usuarios', ['editar' => $usuario->id]) }}#formulario"
                               class="font-medium text-marca-700 hover:underline">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $usuarios->links() }}</div>

    <section id="formulario" class="mt-10 max-w-2xl rounded-xl border border-tinta-200 bg-white p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-tinta-700">
                {{ $editando ? 'Editar '.$editando->name : 'Crear usuario' }}
            </h2>
            @if ($editando)
                <a href="{{ route('panel.usuarios') }}" class="text-sm text-marca-700 hover:underline">Cancelar</a>
            @endif
        </div>

        @if ($editando?->is(auth()->user()))
            <p class="mt-3 rounded-lg bg-tinta-100 p-3 text-sm text-tinta-600">
                Es tu propia cuenta: puedes cambiar tus datos, pero no tu rol ni desactivarte.
                Si no, el panel podría quedarse sin administrador.
            </p>
        @endif

        <form method="post"
              action="{{ $editando ? route('panel.usuarios.actualizar', $editando) : route('panel.usuarios.guardar') }}"
              class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf
            @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600'; @endphp

            <div>
                <label for="name" class="text-sm font-medium">Nombre</label>
                <input id="name" name="name" value="{{ old('name', $editando?->name) }}" required class="{{ $campo }}">
            </div>
            <div>
                <label for="email" class="text-sm font-medium">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email', $editando?->email) }}" required class="{{ $campo }}">
            </div>
            <div>
                <label for="telefono" class="text-sm font-medium">Teléfono</label>
                <input id="telefono" name="telefono" value="{{ old('telefono', $editando?->telefono) }}" class="{{ $campo }}">
            </div>
            <div>
                <label for="rol" class="text-sm font-medium">Rol</label>
                <select id="rol" name="rol" @disabled($editando?->is(auth()->user())) class="{{ $campo }}">
                    @foreach ($roles as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('rol', $editando?->rol->value) === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="password" class="text-sm font-medium">
                    Contraseña @if ($editando)<span class="font-normal text-tinta-500">— déjala vacía para no cambiarla</span>@endif
                </label>
                <input id="password" type="password" name="password" @required(! $editando)
                       autocomplete="new-password" class="{{ $campo }}">
            </div>
            <div>
                <label for="password_confirmation" class="text-sm font-medium">Repetir contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" @required(! $editando)
                       autocomplete="new-password" class="{{ $campo }}">
            </div>

            {{-- Con la consecuencia escrita: desmarcar esto no es «archivar»,
                 le cierra la sesión a esa persona en el acto y en todos sus
                 equipos. Sin decirlo, el admin no sabe qué está haciendo. --}}
            <div class="sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="activo" value="1"
                           @checked(old('activo', $editando?->activo ?? true))
                           @disabled($editando?->is(auth()->user()))
                           class="size-4 rounded border-tinta-300 text-marca-700">
                    Cuenta activa
                </label>
                <p class="ml-6 mt-1 text-xs text-tinta-500">
                    Si la desmarcas, esa persona deja de poder entrar y se le cierra la sesión
                    al instante, también donde tuviera marcado «no cerrar sesión». Se puede volver a activar.
                </p>
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                    {{ $editando ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
            </div>
        </form>
    </section>
@endsection
