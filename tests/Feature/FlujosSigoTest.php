<?php

namespace Tests\Feature;

use App\Models\Agremiado;
use App\Models\Apoyo;
use App\Models\Cuota;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\SysLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Flujos principales de SIGO sobre una base SQLite en memoria (nunca la de MySQL). */
class FlujosSigoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Las pruebas solo corren con SQLite en memoria.');
        }
        $this->artisan('migrate');
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::where('email', 'admin@sigo.mx')->first();
    }

    public function test_todas_las_pantallas_cargan(): void
    {
        $this->actingAs($this->admin());
        foreach (['/', '/agremiados', '/coordinacion', '/cuotas', '/apoyos', '/tienda', '/tienda?tab=pedidos', '/capacitacion', '/gps', '/bitacora', '/perfiles', '/app'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('/agremiados/'.Agremiado::first()->id)->assertOk()->assertJsonStructure(['folio', 'cuotas', 'incidencias', 'traza']);
    }

    public function test_alta_de_agremiado_con_trazabilidad_y_validaciones(): void
    {
        $this->actingAs($this->admin());
        $datos = [
            'nombre' => 'Laura', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Gómez', 'curp' => 'PEGL900101MHGRMRA1', 'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'Mujer', 'clave_elector' => 'PRGMLR90010113M999', 'seccion' => '712', 'calle' => 'Juárez 1', 'colonia' => 'Centro', 'cp' => '42000',
            'municipio' => 'Pachuca de Soto', 'estado' => 'Hidalgo', 'celular' => '7711234567', 'emergencia_nombre' => 'Juan Pérez', 'emergencia_telefono' => '7717654321',
            'puesto' => 'Operadora', 'coordinador_id' => Agremiado::first()->id, 'fecha_ingreso' => today()->toDateString(), 'gps_autorizado' => '1',
            'foto' => UploadedFile::fake()->image('cara.jpg'),
        ];
        $this->post('/agremiados', $datos)->assertSessionHasNoErrors();
        $a = Agremiado::where('curp', 'PEGL900101MHGRMRA1')->firstOrFail();
        $this->assertSame('SUT-0202', $a->folio);
        $this->assertSame($this->admin()->id, $a->created_by);
        $this->assertNotNull($a->foto);
        $this->assertTrue(SysLog::where('tabla_afectada', 'agremiados')->where('registro_id', $a->id)->where('accion', 'Alta')->exists());

        // CURP repetida y fecha de ingreso futura
        $this->post('/agremiados', [...$datos, 'clave_elector' => 'OTRA00000000000000', 'fecha_ingreso' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors(['curp', 'fecha_ingreso']);
    }

    public function test_no_se_da_de_baja_a_quien_coordina_y_la_baja_es_logica(): void
    {
        $this->actingAs($this->admin());
        $coord = Agremiado::where('folio', 'SUT-0047')->first();
        $this->post("/agremiados/{$coord->id}/baja", ['motivo' => 'Jubilación'])->assertStatus(422);

        $hoja = Agremiado::where('folio', 'SUT-0201')->first();
        $this->post("/agremiados/{$hoja->id}/baja", ['motivo' => 'Renuncia voluntaria'])->assertSessionHasNoErrors();
        $this->assertSame('Baja', $hoja->fresh()->estatus);
        $this->assertSame($this->admin()->id, $hoja->fresh()->baja_por);
    }

    public function test_pago_de_cuotas_y_adeudo(): void
    {
        $this->actingAs($this->admin());
        $a = Agremiado::where('folio', 'SUT-0158')->first(); // debe 3 meses
        $antes = Cuota::where('agremiado_id', $a->id)->count();
        $this->post('/cuotas', ['agremiado_id' => $a->id, 'meses' => ['2026-09', '2026-10'], 'forma_pago' => 'Efectivo en tesorería'])->assertSessionHasNoErrors();
        $this->assertSame($antes + 2, Cuota::where('agremiado_id', $a->id)->count());
        // Mes ya pagado
        $this->post('/cuotas', ['agremiado_id' => $a->id, 'meses' => ['2026-09'], 'forma_pago' => 'Efectivo en tesorería'])->assertStatus(422);
    }

    public function test_apoyo_exige_evidencia_y_la_guarda(): void
    {
        $this->actingAs($this->admin());
        $a = Agremiado::first();
        $base = ['agremiado_id' => $a->id, 'tipo' => 'Despensa', 'detalle' => 'Despensa básica', 'monto' => 450, 'fecha_entrega' => now()->subHour()->format('Y-m-d H:i')];
        $this->post('/apoyos', $base)->assertStatus(422);
        $this->post('/apoyos', [...$base, 'evidencias' => [UploadedFile::fake()->image('entrega.jpg')]])->assertSessionHasNoErrors();
        $this->assertSame(1, Apoyo::latest('id')->first()->archivos()->count());
    }

    public function test_pedido_descuenta_inventario_y_cancelar_lo_devuelve(): void
    {
        $this->actingAs($this->admin());
        $p = Producto::where('nombre', 'Kit escolar secundaria')->first();
        $stock = $p->stock;
        $this->post('/tienda/pedidos', ['agremiado_id' => Agremiado::first()->id, 'forma_pago' => 'Pago en tesorería', 'items' => [['producto_id' => $p->id, 'cantidad' => 2]]])->assertSessionHasNoErrors();
        $this->assertSame($stock - 2, $p->fresh()->stock);
        $pedido = Pedido::latest('id')->first();
        $this->put("/tienda/pedidos/{$pedido->id}/estado", ['estado' => 'Cancelado'])->assertSessionHasNoErrors();
        $this->assertSame($stock, $p->fresh()->stock);
    }

    public function test_permisos_por_perfil(): void
    {
        $coord = User::where('email', 'coordinador@sigo.mx')->first(); // coordinador: tienda sin acceso, agremiados solo ver
        $this->actingAs($coord)->get('/tienda')->assertForbidden();
        $this->actingAs($coord)->post('/agremiados', [])->assertForbidden();

        $agremiado = User::where('email', 'agremiado@sigo.mx')->first();
        $this->actingAs($agremiado)->get('/')->assertRedirect('/app');
        $this->actingAs($agremiado)->get('/app')->assertOk();

        // El Super usuario no se puede limitar
        $this->actingAs($this->admin())->put('/perfiles/'.Rol::where('clave', 'super')->first()->id.'/permiso', ['modulo' => 'tienda', 'nivel' => 'n'])->assertStatus(422);
    }
}
