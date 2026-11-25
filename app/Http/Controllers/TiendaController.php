<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Archivo;
use App\Models\Catalogo;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TiendaController extends Controller
{
    public const ICONOS = [
        'package' => 'Paquete', 'shopping-basket' => 'Despensa', 'backpack' => 'Escolar', 'glasses' => 'Óptica', 'shirt' => 'Ropa',
        'smile' => 'Salud dental', 'shield' => 'Seguro', 'pill' => 'Medicamento', 'dumbbell' => 'Deporte', 'ticket' => 'Evento', 'wrench' => 'Servicio técnico',
    ];

    public function index(Request $request): Response
    {
        $u = $request->user();
        $productos = Producto::with(['proveedor', 'archivos', 'creador', 'editor'])->orderBy('nombre')->get();

        return Inertia::render('Tienda', [
            'tab' => in_array($request->tab, ['productos', 'inventario', 'pedidos', 'proveedores', 'movimientos'], true) ? $request->tab : 'productos',
            'productos' => $productos->map(fn (Producto $p) => [
                ...$p->only(['id', 'nombre', 'categoria', 'proveedor_id', 'icono', 'color', 'descripcion', 'costo', 'precio', 'stock', 'minimo', 'maximo', 'vendidos', 'publicado']),
                'proveedor' => $p->proveedor?->nombre,
                'margen' => $p->precio > 0 ? round(($p->precio - $p->costo) / $p->precio * 100) : 0,
                'bajo' => $p->bajoMinimo(),
                'archivos' => $this->archivosDe($p),
                'traza' => $this->traza($p),
            ]),
            'proveedores' => Proveedor::withCount('productos')->with(['creador', 'editor'])->orderBy('nombre')->get()->map(fn ($v) => [
                ...$v->only(['id', 'nombre', 'rfc', 'contacto', 'telefono', 'correo']), 'productos' => $v->productos_count, 'traza' => $this->traza($v),
            ]),
            'pedidos' => Pedido::with(['agremiado', 'detalles.producto', 'creador', 'editor'])->orderByDesc('id')->get()->map(fn (Pedido $p) => [
                'id' => $p->id, 'folio' => $p->folio, 'agremiado' => $this->persona($p->agremiado), 'cliente' => $p->agremiado?->nombre_completo,
                'total' => $p->total, 'forma_pago' => $p->forma_pago, 'estado' => $p->estado, 'origen' => $p->origen, 'nota' => $p->nota,
                'fecha' => $p->created_at->format('Y-m-d H:i'),
                'articulos' => $p->detalles->map(fn ($d) => ['producto' => $d->producto?->nombre, 'cantidad' => $d->cantidad, 'precio' => $d->precio]),
                'resumen' => $p->detalles->map(fn ($d) => ($d->producto?->nombre).' × '.$d->cantidad)->join(', '),
                'traza' => $this->traza($p),
            ]),
            'movimientos' => MovimientoInventario::with(['producto', 'proveedor', 'creador'])->orderByDesc('id')->take(500)->get()->map(fn ($m) => [
                'id' => $m->id, 'producto' => $m->producto?->nombre, 'tipo' => $m->tipo, 'cantidad' => $m->cantidad, 'stock' => $m->stock_resultante,
                'proveedor' => $m->proveedor?->nombre, 'nota' => $m->nota, 'usuario' => $m->creador?->name, 'fecha' => $m->created_at->format('Y-m-d H:i'),
            ]),
            'agremiados' => Agremiado::activos()->orderBy('nombre')->get()->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre_completo, 'folio' => $a->folio]),
            'iconos' => collect(self::ICONOS)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            'formasPago' => Catalogo::de('forma_pago_tienda'),
            'puede' => ['editar' => $u->puede('tienda', 'e'), 'eliminar' => $u->puede('tienda', 't')],
        ]);
    }

    // ------------------------------------------------------------------ productos

    private function validarProducto(Request $request, ?Producto $p): array
    {
        $serv = $request->categoria === 'Servicio';

        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('productos', 'nombre')->ignore($p?->id)->whereNull('deleted_at')],
            'categoria' => ['required', Rule::in(['Producto', 'Servicio'])],
            'proveedor_id' => [$serv ? 'nullable' : 'required', 'nullable', 'exists:proveedores,id'],
            'icono' => ['required', Rule::in(array_keys(self::ICONOS))],
            'descripcion' => ['required', 'string', 'max:2000'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'precio' => ['required', 'numeric', 'gt:0', 'gte:costo'],
            'stock' => [$serv ? 'nullable' : 'required', 'nullable', 'integer', 'min:0'],
            'minimo' => [$serv ? 'nullable' : 'required', 'nullable', 'integer', 'min:0'],
            'maximo' => [$serv ? 'nullable' : 'required', 'nullable', 'integer', 'gt:minimo'],
            'publicado' => ['boolean'],
            'archivos' => ['array', 'max:12'],
            'archivos.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,pdf,doc,docx'],
            'quitar' => ['array'],
        ], [
            'proveedor_id.required' => 'Los productos necesitan proveedor.', 'precio.gte' => 'El precio no puede ser menor al costo.',
            'maximo.gt' => 'Debe ser mayor que el mínimo.', 'nombre.unique' => 'Ya existe un producto con ese nombre.',
        ], ['proveedor_id' => 'proveedor', 'descripcion' => 'descripción', 'stock' => 'existencia', 'minimo' => 'mínimo', 'maximo' => 'máximo']);
    }

    public function storeProducto(Request $request): RedirectResponse
    {
        $request->merge(['publicado' => $request->boolean('publicado', true)]);
        $d = $this->validarProducto($request, null);
        DB::transaction(function () use ($d, $request) {
            $serv = $d['categoria'] === 'Servicio';
            $p = Producto::create([...$this->datosProducto($d), 'color' => ['#5f9e2f', '#1f6fae', '#2a7fc4', '#ee8a2c', '#13857f', '#0c2742', '#d9971c'][random_int(0, 6)]]);
            if (! $serv && $p->stock > 0) {
                MovimientoInventario::create(['producto_id' => $p->id, 'tipo' => 'Entrada', 'cantidad' => $p->stock, 'stock_resultante' => $p->stock, 'proveedor_id' => $p->proveedor_id, 'nota' => 'Existencia inicial']);
            }
            foreach ($request->file('archivos', []) as $f) {
                $this->guardarArchivo($p, $f, 'productos');
            }
        });

        return back()->with('success', 'Producto agregado: '.$d['nombre']);
    }

    public function updateProducto(Request $request, Producto $producto): RedirectResponse
    {
        $request->merge(['publicado' => $request->boolean('publicado', true)]);
        $d = $this->validarProducto($request, $producto);
        DB::transaction(function () use ($producto, $d, $request) {
            $antes = $producto->stock;
            $producto->update($this->datosProducto($d));
            if ($producto->stock !== null && $antes !== null && $producto->stock !== $antes) {
                MovimientoInventario::create(['producto_id' => $producto->id, 'tipo' => 'Ajuste', 'cantidad' => $producto->stock - $antes, 'stock_resultante' => $producto->stock, 'nota' => 'Ajuste desde la edición del producto']);
            }
            Archivo::whereIn('id', $d['quitar'] ?? [])->where('archivable_type', Producto::class)->where('archivable_id', $producto->id)->get()->each->delete();
            foreach ($request->file('archivos', []) as $f) {
                $this->guardarArchivo($producto, $f, 'productos');
            }
        });

        return back()->with('success', 'Producto actualizado');
    }

    private function datosProducto(array $d): array
    {
        $serv = $d['categoria'] === 'Servicio';

        return [
            ...collect($d)->only(['nombre', 'categoria', 'proveedor_id', 'icono', 'descripcion', 'precio', 'publicado'])->all(),
            'costo' => $d['costo'] ?? 0,
            'proveedor_id' => $d['proveedor_id'] ?? null,
            'stock' => $serv ? null : $d['stock'], 'minimo' => $serv ? null : $d['minimo'], 'maximo' => $serv ? null : $d['maximo'],
        ];
    }

    public function destroyProducto(Producto $producto): RedirectResponse
    {
        abort_if($producto->stock > 0, 422, 'El producto todavía tiene existencia. Ajústala a 0 antes de eliminarlo.');
        $producto->delete();

        return back()->with('success', 'Producto eliminado');
    }

    public function entrada(Request $request, Producto $producto): RedirectResponse
    {
        abort_if($producto->esServicio(), 422, 'Los servicios no llevan inventario.');
        $d = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($producto, $d) {
            $producto->increment('stock', $d['cantidad']);
            MovimientoInventario::create(['producto_id' => $producto->id, 'tipo' => 'Entrada', 'cantidad' => $d['cantidad'], 'stock_resultante' => $producto->fresh()->stock,
                'proveedor_id' => $d['proveedor_id'] ?? $producto->proveedor_id, 'nota' => $d['nota'] ?? null]);
        });

        return back()->with('success', "Entrada registrada: {$producto->nombre} +{$d['cantidad']}");
    }

    // ------------------------------------------------------------------ proveedores

    private function validarProveedor(Request $request, ?Proveedor $v): array
    {
        $request->merge(['rfc' => mb_strtoupper(trim((string) $request->rfc)), 'telefono' => preg_replace('/\D/', '', (string) $request->telefono)]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'rfc' => ['required', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', Rule::unique('proveedores', 'rfc')->ignore($v?->id)->whereNull('deleted_at')],
            'contacto' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'regex:/^\d{10}$/'],
            'correo' => ['nullable', 'email', 'max:120'],
        ], ['rfc.regex' => 'El RFC no tiene un formato válido.', 'rfc.unique' => 'Ya existe un proveedor con este RFC.', 'telefono.regex' => 'Debe tener 10 dígitos.'], ['nombre' => 'razón social', 'contacto' => 'persona de contacto', 'telefono' => 'teléfono']);
    }

    public function storeProveedor(Request $request): RedirectResponse
    {
        Proveedor::create($this->validarProveedor($request, null));

        return back()->with('success', 'Proveedor agregado');
    }

    public function updateProveedor(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($this->validarProveedor($request, $proveedor));

        return back()->with('success', 'Proveedor actualizado');
    }

    public function destroyProveedor(Proveedor $proveedor): RedirectResponse
    {
        abort_if($proveedor->productos()->exists(), 422, 'El proveedor tiene productos asignados.');
        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado');
    }

    // ------------------------------------------------------------------ pedidos

    /** Crea un pedido (desde el panel o desde la app) y descuenta inventario. */
    public static function crearPedido(int $agremiadoId, array $items, string $formaPago, string $origen, ?string $nota = null): Pedido
    {
        return DB::transaction(function () use ($agremiadoId, $items, $formaPago, $origen, $nota) {
            $total = 0;
            $lineas = [];
            foreach ($items as $it) {
                $p = Producto::lockForUpdate()->findOrFail($it['producto_id']);
                $n = (int) $it['cantidad'];
                abort_if(! $p->publicado, 422, "«{$p->nombre}» no está disponible.");
                abort_if(! $p->esServicio() && $p->stock < $n, 422, "No hay existencia suficiente de «{$p->nombre}» (quedan {$p->stock}).");
                $lineas[] = [$p, $n];
                $total += $p->precio * $n;
            }
            $sig = (int) Pedido::withTrashed()->max('id') + 1;
            $pedido = Pedido::create(['folio' => 'PED-'.str_pad((string) $sig, 5, '0', STR_PAD_LEFT), 'agremiado_id' => $agremiadoId, 'total' => $total, 'forma_pago' => $formaPago, 'origen' => $origen, 'nota' => $nota]);
            foreach ($lineas as [$p, $n]) {
                $pedido->detalles()->create(['producto_id' => $p->id, 'cantidad' => $n, 'precio' => $p->precio]);
                $p->increment('vendidos', $n);
                if (! $p->esServicio()) {
                    $p->decrement('stock', $n);
                    MovimientoInventario::create(['producto_id' => $p->id, 'tipo' => 'Salida', 'cantidad' => -$n, 'stock_resultante' => $p->fresh()->stock, 'nota' => "Pedido {$pedido->folio}"]);
                }
            }
            Catalogo::asegurar('forma_pago_tienda', $formaPago);

            return $pedido;
        });
    }

    public function storePedido(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'agremiado_id' => ['required', Rule::exists('agremiados', 'id')->where('estatus', 'Activo')],
            'forma_pago' => ['required', 'string', 'max:40'],
            'nota' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ], ['items.required' => 'Agrega al menos un producto.'], ['agremiado_id' => 'agremiado', 'forma_pago' => 'forma de pago']);
        $p = self::crearPedido($d['agremiado_id'], $d['items'], $d['forma_pago'], 'Panel', $d['nota'] ?? null);

        return back()->with('success', "Pedido {$p->folio} registrado");
    }

    public function estadoPedido(Request $request, Pedido $pedido): RedirectResponse
    {
        $d = $request->validate(['estado' => ['required', Rule::in(['Entregado', 'Cancelado'])]]);
        abort_if($pedido->estado !== 'Pendiente', 422, 'Solo se pueden cambiar pedidos pendientes.');
        DB::transaction(function () use ($pedido, $d) {
            if ($d['estado'] === 'Cancelado') {
                foreach ($pedido->detalles()->with('producto')->get() as $det) {
                    $p = $det->producto;
                    $p->decrement('vendidos', $det->cantidad);
                    if (! $p->esServicio()) {
                        $p->increment('stock', $det->cantidad);
                        MovimientoInventario::create(['producto_id' => $p->id, 'tipo' => 'Entrada', 'cantidad' => $det->cantidad, 'stock_resultante' => $p->fresh()->stock, 'nota' => "Cancelación {$pedido->folio}"]);
                    }
                }
            }
            $pedido->update(['estado' => $d['estado']]);
        });

        return back()->with('success', "Pedido {$pedido->folio}: {$d['estado']}");
    }
}
