<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Agremiado;
use App\Models\Ajuste;
use App\Models\Apoyo;
use App\Models\Catalogo;
use App\Models\Cuota;
use App\Models\Curso;
use App\Models\CursoInscripcion;
use App\Models\Incidencia;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\SysLog;
use App\Models\User;
use App\Support\Permisos;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Datos de ejemplo tomados del prototipo SIGO. */
class DatabaseSeeder extends Seeder
{
    /** Contraseña de las cuentas de demostración (solo se muestra en el login en entorno local). */
    public const PASSWORD_DEMO = 'Sigo2026!';

    public function run(): void
    {
        $roles = $this->roles();
        $this->catalogos();
        Ajuste::updateOrCreate(['clave' => 'cuota_mensual'], ['valor' => '150']);
        Ajuste::updateOrCreate(['clave' => 'cuotas_desde'], ['valor' => '2026-06']);

        $super = User::create(['name' => 'Administrador SIGO', 'email' => 'admin@sigo.mx', 'password' => self::PASSWORD_DEMO, 'rol_id' => $roles['super']->id]);
        Auth::setUser($super);

        $P = $this->agremiados();
        $cuentas = [
            [1, 'secretaria@sigo.mx', 'secretario'], [11, 'tesoreria@sigo.mx', 'admin'],
            [2, 'coordinador@sigo.mx', 'coord'], [3, 'coordinadora.sur@sigo.mx', 'coord'], [4, 'promotor@sigo.mx', 'coord'], [5, 'agremiado@sigo.mx', 'agremiado'],
        ];
        $U = ['super' => $super];
        foreach ($cuentas as [$pid, $mail, $rol]) {
            $U[$pid] = User::create(['name' => $P[$pid]->nombre_completo, 'email' => $mail, 'password' => self::PASSWORD_DEMO, 'rol_id' => $roles[$rol]->id, 'agremiado_id' => $P[$pid]->id]);
        }
        $quien = fn ($pid) => ($U[$pid] ?? $super)->id;

        // Cuotas de junio a noviembre: los últimos «adeudo» meses quedan pendientes
        $adeudos = [4 => 1, 6 => 3, 8 => 2, 10 => 1];
        foreach ($P as $pid => $a) {
            $pagar = 6 - ($adeudos[$pid] ?? 0);
            for ($i = 0; $i < $pagar; $i++) {
                $mes = 6 + $i;
                $f = Carbon::create(2026, $mes, 5 + ($pid % 9), 9 + ($pid % 6), ($pid * 11) % 60);
                $this->como($i % 2 ? $quien(11) : $quien(1), fn () => Cuota::create(['agremiado_id' => $a->id, 'anio' => 2026, 'mes' => $mes, 'monto' => 150, 'forma_pago' => $i % 3 ? 'Descuento vía nómina' : 'Efectivo en tesorería', 'created_at' => $f, 'updated_at' => $f]));
            }
        }

        foreach ([
            [6, '2026-10-28 09:14', 4, 'Inasistencia', 'No asistió a la asamblea de la sección del 27/10.'],
            [8, '2026-10-15 13:40', 3, 'Reconocimiento', 'Apoyó en la logística de la jornada de salud.'],
            [5, '2026-09-30 17:05', 2, 'Reconocimiento', 'Capacitó a compañeros de nuevo ingreso en el sistema.'],
            [10, '2026-09-12 11:22', 3, 'Observación', 'Pendiente entregar comprobante de domicilio actualizado.'],
        ] as [$pid, $f, $por, $tipo, $txt]) {
            $this->como($quien($por), fn () => Incidencia::create(['agremiado_id' => $P[$pid]->id, 'tipo' => $tipo, 'descripcion' => $txt, 'fecha' => substr($f, 0, 10), 'created_at' => $f, 'updated_at' => $f]));
        }

        foreach ([
            [9, 'Apoyo escolar', 'Kit de útiles nivel secundaria (2 piezas)', 820, '2026-11-03 10:42', 3],
            [6, 'Despensa', 'Despensa básica mensual', 450, '2026-10-30 16:10', 4],
            [7, 'Apoyo económico', 'Gastos médicos por cirugía de familiar directo', 1500, '2026-10-22 12:31', 1],
            [12, 'Lentes', 'Lentes graduados (convenio Óptica Valle)', 690, '2026-10-09 09:58', 2],
            [8, 'Despensa', 'Despensa básica mensual', 450, '2026-09-29 15:02', 3],
            [10, 'Apoyo escolar', 'Kit de útiles nivel primaria', 640, '2026-08-21 11:47', 3],
        ] as [$pid, $tipo, $det, $monto, $f, $por]) {
            $this->como($quien($por), fn () => Apoyo::create(['agremiado_id' => $P[$pid]->id, 'tipo' => $tipo, 'detalle' => $det, 'monto' => $monto, 'fecha_entrega' => $f, 'created_at' => $f, 'updated_at' => $f]));
        }

        $this->tienda($P, $quien);
        $this->cursos($P, $quien);
        $this->gps($P, $quien);

        // La bitácora empieza limpia con las acciones del prototipo (las altas del seeder no son acciones reales)
        SysLog::query()->delete();
        foreach ([
            ['2026-11-09 08:02', 11, 'cuotas', 'Registró pago', 'Cuota de noviembre · SUT-0132 · $150.00'],
            ['2026-11-08 19:47', 5, 'tienda', 'Compró en tienda', 'Kit escolar secundaria × 1 · $410.00 (desde la app)'],
            ['2026-11-08 12:15', 'super', 'perfiles', 'Modificó permisos', 'Coordinador / Dirigente · Apoyos: Ver → Editar'],
            ['2026-11-07 17:30', 2, 'ubicacion', 'Inició seguimiento GPS', 'Brigada de afiliación · 3 participantes'],
            ['2026-11-07 10:04', 1, 'agremiados', 'Agregó incidencia', 'SUT-0171 · Reconocimiento'],
            ['2026-11-05 13:10', 11, 'tienda', 'Modificó producto', 'Despensa básica · precio $430.00 → $450.00'],
            ['2026-11-03 10:42', 1, 'apoyos', 'Registró entrega de apoyo', 'Apoyo escolar a SUT-0184 · 3 evidencias'],
            ['2026-11-02 09:20', 11, 'tienda', 'Registró entrada de inventario', 'Kit escolar secundaria +40 · Papelería La Escolar'],
            ['2026-10-30 16:10', 2, 'apoyos', 'Registró entrega de apoyo', 'Despensa a SUT-0158 · 1 evidencia'],
            ['2026-10-29 13:55', 1, 'red', 'Reasignó coordinador', 'SUT-0201 · Patricia Salinas → Ricardo Domínguez'],
            ['2026-10-28 09:14', 2, 'agremiados', 'Agregó incidencia', 'SUT-0158 · Inasistencia'],
        ] as [$f, $u, $m, $acc, $d]) {
            SysLog::create(['modulo' => $m, 'accion' => $acc, 'descripcion' => $d, 'usuario_id' => ($U[$u] ?? $super)->id, 'fecha' => substr($f, 0, 10), 'hora' => substr($f, 11).':00', 'ip' => '127.0.0.1', 'created_at' => $f]);
        }

        $this->codigosPostales();
        Auth::logout();
    }

    private function como(int $userId, callable $fn)
    {
        $antes = Auth::user();
        Auth::setUser(User::find($userId));
        $r = $fn();
        Auth::setUser($antes);

        return $r;
    }

    private function roles(): array
    {
        $def = [
            'super' => ['Super usuario', 'Control total del sistema. Configura perfiles y no puede limitarse.', 'crown', '#0c2742', true, false, Permisos::vacio('t')],
            'secretario' => ['Secretario General / Presidente', 'Dirige la organización: ve todo y autoriza movimientos.', 'landmark', '#0f3b63', false, false,
                ['inicio' => 't', 'agremiados' => 't', 'red' => 't', 'cuotas' => 't', 'apoyos' => 't', 'tienda' => 'e', 'capacitacion' => 'e', 'ubicacion' => 'v', 'bitacora' => 'v', 'perfiles' => 't']],
            'admin' => ['Administrativo', 'Tesorería y operación: cuotas, tienda y padrón.', 'briefcase', '#1f6fae', false, false,
                ['inicio' => 'v', 'agremiados' => 'e', 'red' => 'v', 'cuotas' => 'e', 'apoyos' => 'e', 'tienda' => 't', 'capacitacion' => 'v', 'ubicacion' => 'n', 'bitacora' => 'v', 'perfiles' => 'n']],
            'coord' => ['Coordinador / Dirigente', 'Coordina a su red de agremiados y registra apoyos.', 'network', '#2a7fc4', false, false,
                ['inicio' => 'v', 'agremiados' => 'v', 'red' => 'v', 'cuotas' => 'v', 'apoyos' => 'e', 'tienda' => 'n', 'capacitacion' => 'v', 'ubicacion' => 'e', 'bitacora' => 'n', 'perfiles' => 'n']],
            'agremiado' => ['Agremiado / Asociado', 'Consulta sus cuotas, apoyos, cursos y compra en la tienda desde la app.', 'user-round', '#18a3a0', false, true, Permisos::vacio('n')],
        ];
        $r = [];
        foreach ($def as $clave => [$nombre, $desc, $ico, $color, $prot, $portal, $perm]) {
            $r[$clave] = Rol::create(['clave' => $clave, 'nombre' => $nombre, 'descripcion' => $desc, 'icono' => $ico, 'color' => $color, 'protegido' => $prot, 'portal' => $portal, 'permisos' => $perm]);
        }

        return $r;
    }

    private function catalogos(): void
    {
        $c = [
            'tipo_apoyo' => ['Despensa', 'Apoyo escolar', 'Apoyo económico', 'Lentes', 'Medicamento'],
            'tipo_incidencia' => ['Observación', 'Inasistencia', 'Reconocimiento', 'Sanción'],
            'motivo_baja' => ['Renuncia voluntaria', 'Jubilación', 'Cambio de centro de trabajo', 'Fallecimiento', 'Expulsión por estatutos'],
            'forma_pago' => ['Efectivo en tesorería', 'Descuento vía nómina', 'Transferencia'],
            'forma_pago_tienda' => ['Descuento vía nómina', 'Pago en tesorería'],
            'puesto' => ['Secretaria General', 'Coordinador de zona', 'Promotor de centro de trabajo', 'Auxiliar administrativo', 'Operador', 'Enfermera', 'Chofer', 'Docente', 'Intendente', 'Técnico de mantenimiento'],
            'estado' => ['Hidalgo', 'Estado de México', 'Ciudad de México', 'Puebla', 'Querétaro', 'Tlaxcala', 'Veracruz'],
        ];
        foreach ($c as $grupo => $valores) {
            foreach ($valores as $i => $v) {
                Catalogo::create(['grupo' => $grupo, 'valor' => $v, 'orden' => $i]);
            }
        }
    }

    /** @return array<int, Agremiado> */
    private function agremiados(): array
    {
        $datos = [
            [1, 'Ma. Elena', 'Rangel', 'Cruz', 'M', 'Secretaria General', null, 'SUT-0001', '2009-03-02', 'Pachuca de Soto', '0712', '7712145590', 'Arturo Rangel', '7711083321', 'Av. Juárez 418', 'Centro', '42000', true, '#0f3b63', '1971-05-12'],
            [2, 'Jorge Luis', 'Hernández', 'Ortiz', 'H', 'Coordinador zona norte', 1, 'SUT-0014', '2013-08-19', 'Mineral de la Reforma', '0960', '7713309014', 'Laura Ortiz', '7713301188', 'Calle Pino 22', 'Fracc. Los Tuzos', '42184', true, '#2a7fc4', '1980-03-07'],
            [3, 'Patricia', 'Salinas', 'Mejía', 'M', 'Coordinadora zona sur', 1, 'SUT-0021', '2015-01-12', 'Tizayuca', '1418', '7791024471', 'Rubén Salinas', '7791020034', 'Cerrada Olmo 7', 'Haciendas de Tizayuca', '43800', false, '#ee8a2c', '1984-04-09'],
            [4, 'Ricardo', 'Domínguez', 'Vera', 'H', 'Promotor centro de trabajo 3', 2, 'SUT-0047', '2017-05-08', 'Pachuca de Soto', '0745', '7715096620', 'Sofía Vera', '7715091007', 'Priv. Hidalgo 105', 'Morelos', '42040', true, '#2a7fc4', '1988-05-11'],
            [5, 'Ana Karen', 'López', 'Fuentes', 'M', 'Auxiliar administrativa', 2, 'SUT-0132', '2021-02-15', 'Mineral de la Reforma', '0961', '7718702245', 'Gloria Fuentes', '7718700912', 'Calle Roble 64', 'Fracc. Forjadores', '42186', true, '#18a3a0', '1995-06-13'],
            [6, 'Miguel Ángel', 'Torres', 'Ruiz', 'H', 'Operador', 4, 'SUT-0158', '2022-06-01', 'Pachuca de Soto', '0733', '7714567781', 'Irma Ruiz', '7714560020', 'Calle 5 de Mayo 12', 'Periodistas', '42060', false, '#5f9e2f', '1993-07-15'],
            [7, 'Rosa Isela', 'Martínez', 'Gómez', 'M', 'Enfermera', 4, 'SUT-0163', '2022-09-26', 'Pachuca de Soto', '0748', '7716123390', 'José Martínez', '7716128870', 'Blvd. Colosio 980, Int. 4', 'Centro', '42000', true, '#c97a1a', '1990-08-17'],
            [8, 'Héctor', 'Ramírez', 'Soto', 'H', 'Chofer', 3, 'SUT-0171', '2023-01-09', 'Tizayuca', '1422', '7794551290', 'Marta Soto', '7794556631', 'Calle Fresno 31', 'El Carmen', '43800', false, '#0c2742', '1986-09-19'],
            [9, 'Lucía', 'Mendoza', 'Ibarra', 'M', 'Docente', 3, 'SUT-0184', '2023-07-17', 'Tolcayuca', '1503', '7792108812', 'Pedro Mendoza', '7792104417', 'Av. Hidalgo 77', 'Centro', '43860', true, '#13857f', '1997-10-21'],
            [10, 'Fernando', 'Castillo', 'Peña', 'H', 'Intendente', 3, 'SUT-0192', '2024-02-05', 'Tizayuca', '1419', '7793772055', 'Elvia Peña', '7793779910', 'Calle Cedro 9', 'Fracc. Rancho Don Antonio', '43800', false, '#3d5f8f', '1979-11-23'],
            [11, 'Daniela', 'Ortega', 'Vázquez', 'M', 'Administrativa de tesorería', 1, 'SUT-0098', '2019-10-21', 'Pachuca de Soto', '0720', '7719026641', 'Carlos Ortega', '7719021123', 'Calle Allende 301', 'Centro', '42000', false, '#1f6fae', '1992-12-25'],
            [12, 'Samuel', 'Aguilar', 'Reyes', 'H', 'Técnico de mantenimiento', 4, 'SUT-0201', '2024-11-11', 'Mineral de la Reforma', '0958', '7711184407', 'Norma Reyes', '7711183300', 'Calle Cobre 18', 'La Providencia', '42186', true, '#d9971c', '1999-01-27'],
        ];
        $coords = [[20.1220, -98.7330], [20.0790, -98.7150], [19.8390, -98.9800], [20.1120, -98.7420], [20.0700, -98.7000], [20.1300, -98.7500],
            [20.1180, -98.7380], [19.8450, -98.9750], [19.8860, -98.9580], [19.8320, -98.9900], [20.1240, -98.7360], [20.0650, -98.6950]];

        $P = [];
        foreach ($datos as $i => [$id, $nom, $ap, $am, $sx, $puesto, $padre, $folio, $ingreso, $mun, $secc, $cel, $emN, $emT, $calle, $col, $cp, $gps, $color, $nac]) {
            $curp = mb_strtoupper(substr($this->sinAcentos($ap), 0, 2).substr($this->sinAcentos($am), 0, 1).substr($this->sinAcentos($nom), 0, 1))
                .str_replace('-', '', substr($nac, 2)).$sx.'HG'.mb_strtoupper(substr($this->sinAcentos($ap), 2, 1).substr($this->sinAcentos($am), 2, 1).substr($this->sinAcentos($nom), 2, 1)).'A'.($id % 10);
            $clave = substr(mb_strtoupper(substr($this->sinAcentos($ap), 0, 2).substr($this->sinAcentos($am), 0, 2).substr($this->sinAcentos($nom), 0, 2)).str_replace('-', '', substr($nac, 2)).'13M'.str_pad((string) $id, 3, '0', STR_PAD_LEFT), 0, 18);
            $alta = Carbon::parse($ingreso)->setTime(9 + ($i % 7), ($i * 13) % 60);
            $P[$id] = Agremiado::create([
                'folio' => $folio, 'nombre' => $nom === 'Ma. Elena' ? 'María Elena' : $nom, 'apellido_paterno' => $ap, 'apellido_materno' => $am,
                'curp' => $curp, 'fecha_nacimiento' => $nac, 'sexo' => $sx === 'M' ? 'Mujer' : 'Hombre', 'clave_elector' => $clave, 'seccion' => $secc,
                'calle' => $calle, 'colonia' => $col, 'cp' => $cp, 'municipio' => $mun, 'estado' => 'Hidalgo',
                'latitud' => $coords[$i][0], 'longitud' => $coords[$i][1], 'celular' => $cel, 'emergencia_nombre' => $emN, 'emergencia_telefono' => $emT,
                'puesto' => $puesto, 'coordinador_id' => $padre ? $P[$padre]->id : null, 'fecha_ingreso' => $ingreso,
                'gps_autorizado' => $gps, 'gps_autorizado_at' => $gps ? Carbon::create(2026, 8 + ($i % 3), 5 + $i, 18, 10 + $i * 3) : null,
                'color' => $color, 'created_at' => $alta, 'updated_at' => Carbon::create(2026, [9, 10, 10, 11][$i % 4], 3 + ($i % 6) * 2, 10 + ($i % 8), ($i * 7) % 60),
            ]);
        }

        return $P;
    }

    private function sinAcentos(string $t): string
    {
        return strtr(str_replace(['Ma. ', ' '], ['MA', ''], $t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'ñ' => 'x', 'Ñ' => 'X']);
    }

    private function tienda(array $P, callable $quien): void
    {
        $prov = [];
        foreach ([
            ['Abarrotes del Valle de Pachuca', 'Raúl Islas', '7717152030', 'AVP120304KJ2', '2025-02-10 10:12', 11],
            ['Papelería La Escolar', 'Verónica Gil', '7717186402', 'PES990812AB1', '2025-03-04 12:40', 11],
            ['Óptica Valle', 'Dr. Iván Cano', '7717195500', 'OVA151020PQ7', '2025-06-18 09:05', 1],
            ['Deportes Tuzos', 'Mario Peña', '7717128833', 'DTU080115MN4', '2025-08-27 16:33', 11],
        ] as $i => [$n, $c, $t, $rfc, $f, $u]) {
            $prov[$i + 1] = $this->como($quien($u), fn () => Proveedor::create(['nombre' => $n, 'contacto' => $c, 'telefono' => $t, 'rfc' => $rfc, 'created_at' => $f, 'updated_at' => $f]));
        }
        $prods = [];
        foreach ([
            ['Despensa básica', 'Producto', 1, 390, 450, 14, 20, 80, 'shopping-basket', '#5f9e2f', 38, '18 artículos de la canasta básica: arroz, frijol, aceite, azúcar, atún, pasta y productos de higiene.'],
            ['Kit escolar secundaria', 'Producto', 2, 310, 410, 52, 25, 120, 'backpack', '#1f6fae', 64, 'Mochila, 6 cuadernos profesionales, juego de geometría, colores y calculadora científica.'],
            ['Lentes graduados', 'Producto', 3, 520, 690, 9, 5, 30, 'glasses', '#2a7fc4', 17, 'Armazón y micas graduadas con antirreflejante. Incluye examen de la vista en Óptica Valle.'],
            ['Uniforme deportivo', 'Producto', 4, 380, 520, 6, 10, 40, 'shirt', '#ee8a2c', 29, 'Pants y playera con el logo de la sección. Tallas CH a XXL.'],
            ['Consulta dental', 'Servicio', null, 0, 150, null, null, null, 'smile', '#13857f', 45, 'Consulta y limpieza en consultorio con convenio. Se agenda desde la app.'],
            ['Seguro de gastos funerarios', 'Servicio', null, 0, 95, null, null, null, 'shield', '#0c2742', 112, 'Cobertura mensual para el agremiado y 4 beneficiarios. Se descuenta con la cuota.'],
        ] as $i => [$n, $cat, $pv, $costo, $precio, $stock, $min, $max, $ico, $color, $vend, $desc]) {
            $p = $this->como($quien(11), fn () => Producto::create(['nombre' => $n, 'categoria' => $cat, 'proveedor_id' => $pv ? $prov[$pv]->id : null, 'costo' => $costo, 'precio' => $precio,
                'stock' => $stock, 'minimo' => $min, 'maximo' => $max, 'icono' => $ico, 'color' => $color, 'vendidos' => $vend, 'descripcion' => $desc, 'created_at' => '2026-0'.(6 + $i % 3).'-10 10:00']));
            $prods[$i + 1] = $p;
            if ($stock !== null) {
                $this->como($quien(11), fn () => MovimientoInventario::create(['producto_id' => $p->id, 'tipo' => 'Entrada', 'cantidad' => $stock, 'stock_resultante' => $stock, 'proveedor_id' => $p->proveedor_id, 'nota' => 'Existencia inicial', 'created_at' => '2026-10-01 09:00']));
            }
        }
        // Un pedido desde la app y otro en tesorería
        foreach ([[5, 2, 1, 'App', 'Entregado', '2026-11-08 19:47', 5], [6, 1, 1, 'Panel', 'Pendiente', '2026-11-20 12:10', 11]] as $k => [$pid, $prod, $n, $origen, $estado, $f, $u]) {
            $this->como($quien($u), function () use ($P, $prods, $pid, $prod, $n, $origen, $estado, $f, $k) {
                $ped = Pedido::create(['folio' => 'PED-'.str_pad((string) ($k + 1), 5, '0', STR_PAD_LEFT), 'agremiado_id' => $P[$pid]->id, 'total' => $prods[$prod]->precio * $n,
                    'forma_pago' => 'Descuento vía nómina', 'estado' => $estado, 'origen' => $origen, 'created_at' => $f, 'updated_at' => $f]);
                PedidoDetalle::create(['pedido_id' => $ped->id, 'producto_id' => $prods[$prod]->id, 'cantidad' => $n, 'precio' => $prods[$prod]->precio]);
            });
        }
    }

    private function cursos(array $P, callable $quien): void
    {
        foreach ([
            ['Derechos laborales y LFT', 6, 72, '2026-08-04 10:00', 1, null],
            ['Seguridad e higiene (NOM-030)', 8, 45, '2026-09-01 09:30', 1, null],
            ['Finanzas personales', 4, 88, '2026-07-15 12:00', 11, null],
            ['Primeros auxilios', 10, 30, '2026-10-06 08:45', 2, 2],
            ['Excel básico', 12, 18, '2026-10-20 16:20', 11, 3],
        ] as $k => [$n, $h, $av, $f, $u, $red]) {
            $this->como($quien($u), function () use ($P, $n, $h, $av, $f, $red, $k) {
                $c = Curso::create(['nombre' => $n, 'horas' => $h, 'modalidad' => 'En línea', 'enlace' => 'https://sais.example.org/cursos/'.($k + 1),
                    'descripcion' => 'Curso de la plataforma SAIS adaptada a la organización.', 'dirigido_coordinador_id' => $red ? $P[$red]->id : null, 'created_at' => $f, 'updated_at' => $f]);
                $ids = $red ? [$P[$red]->id, ...Agremiado::descendientes($P[$red]->id)] : collect($P)->pluck('id')->all();
                foreach ($ids as $j => $id) {
                    $a = max(0, min(100, $av + (($j * 37) % 60) - 30));
                    CursoInscripcion::create(['curso_id' => $c->id, 'agremiado_id' => $id, 'avance' => $a, 'concluido_at' => $a >= 100 ? '2026-11-01 10:00' : null, 'created_at' => $f, 'updated_at' => $f]);
                }
            });
        }
    }

    private function gps(array $P, callable $quien): void
    {
        foreach ([
            [2, 'Brigada de afiliación · Col. Los Tuzos', [[2, 20.0855, -98.7121], [5, 20.0712, -98.7032]]],
            [4, 'Jornada de salud · Hospital General', [[7, 20.1094, -98.7531]]],
            [3, 'Traslado a asamblea estatal', [[9, 19.9655, -98.8625]]],
        ] as [$u, $n, $parts]) {
            $this->como($quien($u), function () use ($P, $n, $parts) {
                $a = Actividad::create(['nombre' => $n, 'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)]);
                foreach ($parts as $i => [$pid, $lat, $lng]) {
                    $a->participantes()->create(['agremiado_id' => $P[$pid]->id, 'latitud' => $lat, 'longitud' => $lng, 'precision' => 12, 'ultima_senal' => now()->subSeconds(20 + $i * 15)]);
                }
            });
        }
    }

    /** Copia el catálogo SEPOMEX desde la base de SIREH si existe en el mismo servidor. */
    private function codigosPostales(): void
    {
        try {
            if (DB::table('codigos_postales')->exists()) {
                return;
            }
            $origen = collect(DB::select("SELECT SCHEMA_NAME s FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'sireh'"))->isNotEmpty();
            if ($origen && Schema::hasTable('codigos_postales')) {
                DB::statement('INSERT INTO codigos_postales (cp, asentamiento, tipo, municipio, estado, ciudad) SELECT cp, asentamiento, tipo, municipio, estado, ciudad FROM sireh.codigos_postales');
            }
        } catch (\Throwable $e) {
            $this->command?->warn('Catálogo de códigos postales no copiado: '.$e->getMessage());
        }
    }
}
