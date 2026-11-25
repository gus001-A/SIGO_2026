<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SIGO · Sistema Integral de Gestión Organizacional.
 * Todas las tablas de captura llevan trazabilidad (created_by / updated_by + fecha y hora) y baja lógica.
 */
return new class extends Migration
{
    private function traza(Blueprint $t): void
    {
        $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $t->timestamps();
        $t->softDeletes();
    }

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 40)->unique();
            $t->string('nombre', 120);
            $t->string('descripcion', 255)->nullable();
            $t->string('icono', 40)->default('user-cog');
            $t->string('color', 9)->default('#2a7fc4');
            $t->json('permisos');
            $t->boolean('protegido')->default(false);
            $t->boolean('portal')->default(false); // entra a la app del agremiado
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('rol_id')->nullable()->after('password')->constrained('roles');
            $t->unsignedBigInteger('agremiado_id')->nullable()->after('rol_id');
            $t->boolean('activo')->default(true)->after('agremiado_id');
            $t->timestamp('ultimo_acceso')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->softDeletes();
        });

        Schema::create('agremiados', function (Blueprint $t) {
            $t->id();
            $t->string('folio', 20)->unique();
            $t->string('nombre', 80);
            $t->string('apellido_paterno', 60);
            $t->string('apellido_materno', 60)->nullable();
            $t->string('curp', 18)->unique();
            $t->date('fecha_nacimiento');
            $t->string('sexo', 20);
            $t->string('clave_elector', 18)->unique();
            $t->string('seccion', 4);
            $t->string('calle', 150);
            $t->string('colonia', 120);
            $t->string('cp', 5);
            $t->string('municipio', 120);
            $t->string('estado', 60);
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->string('celular', 15);
            $t->string('emergencia_nombre', 120);
            $t->string('emergencia_telefono', 15);
            $t->string('puesto', 120);
            $t->foreignId('coordinador_id')->nullable()->constrained('agremiados')->nullOnDelete();
            $t->date('fecha_ingreso');
            $t->boolean('gps_autorizado')->default(false);
            $t->timestamp('gps_autorizado_at')->nullable();
            $t->foreignId('gps_autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->string('foto')->nullable();
            $t->string('ine_frente')->nullable();
            $t->string('ine_reverso')->nullable();
            $t->string('color', 9)->default('#2a7fc4');
            $t->string('estatus', 12)->default('Activo');
            $t->string('baja_motivo', 120)->nullable();
            $t->string('baja_detalle', 255)->nullable();
            $t->timestamp('baja_at')->nullable();
            $t->foreignId('baja_por')->nullable()->constrained('users')->nullOnDelete();
            $this->traza($t);
            $t->index(['estatus', 'coordinador_id']);
        });

        Schema::table('users', fn (Blueprint $t) => $t->foreign('agremiado_id')->references('id')->on('agremiados')->nullOnDelete());

        Schema::create('incidencias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->string('tipo', 60);
            $t->text('descripcion');
            $t->date('fecha');
            $this->traza($t);
        });

        Schema::create('cuotas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->unsignedSmallInteger('anio');
            $t->unsignedTinyInteger('mes');
            $t->decimal('monto', 10, 2);
            $t->string('forma_pago', 40)->default('Efectivo en tesorería');
            $t->string('referencia', 80)->nullable();
            $this->traza($t);
            $t->index(['agremiado_id', 'anio', 'mes']);
        });

        Schema::create('apoyos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->string('tipo', 80);
            $t->string('detalle', 255);
            $t->decimal('monto', 10, 2)->default(0);
            $t->dateTime('fecha_entrega');
            $this->traza($t);
        });

        Schema::create('proveedores', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->string('rfc', 13);
            $t->string('contacto', 120);
            $t->string('telefono', 15);
            $t->string('correo', 120)->nullable();
            $this->traza($t);
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->string('categoria', 20)->default('Producto');
            $t->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $t->string('icono', 40)->default('package');
            $t->string('color', 9)->default('#2a7fc4');
            $t->text('descripcion');
            $t->decimal('costo', 10, 2)->default(0);
            $t->decimal('precio', 10, 2);
            $t->integer('stock')->nullable();
            $t->integer('minimo')->nullable();
            $t->integer('maximo')->nullable();
            $t->unsignedInteger('vendidos')->default(0);
            $t->boolean('publicado')->default(true);
            $this->traza($t);
        });

        // Archivos de cualquier registro: evidencias de apoyos, fotos y documentos de productos
        Schema::create('archivos', function (Blueprint $t) {
            $t->id();
            $t->morphs('archivable');
            $t->string('ruta');
            $t->string('nombre', 200);
            $t->string('tipo', 20); // imagen | video | documento
            $t->unsignedInteger('peso')->default(0);
            $this->traza($t);
        });

        Schema::create('movimientos_inventario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos');
            $t->string('tipo', 20); // Entrada | Salida | Ajuste
            $t->integer('cantidad');
            $t->integer('stock_resultante');
            $t->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $t->string('nota', 255)->nullable();
            $this->traza($t);
        });

        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->string('folio', 20)->unique();
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->decimal('total', 10, 2);
            $t->string('forma_pago', 40);
            $t->string('estado', 20)->default('Pendiente'); // Pendiente | Entregado | Cancelado
            $t->string('origen', 20)->default('Panel'); // Panel | App
            $t->string('nota', 255)->nullable();
            $this->traza($t);
        });

        Schema::create('pedido_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos');
            $t->unsignedInteger('cantidad');
            $t->decimal('precio', 10, 2);
            $t->timestamps();
        });

        Schema::create('cursos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->text('descripcion')->nullable();
            $t->unsignedSmallInteger('horas');
            $t->string('modalidad', 30)->default('En línea');
            $t->string('enlace', 255)->nullable();
            $t->foreignId('dirigido_coordinador_id')->nullable()->constrained('agremiados')->nullOnDelete();
            $t->date('fecha_limite')->nullable();
            $this->traza($t);
        });

        Schema::create('curso_inscripciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('curso_id')->constrained('cursos');
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->unsignedTinyInteger('avance')->default(0);
            $t->timestamp('concluido_at')->nullable();
            $this->traza($t);
            $t->unique(['curso_id', 'agremiado_id']);
        });

        Schema::create('actividades', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->string('estado', 20)->default('Activa'); // Activa | Finalizada
            $t->timestamp('finalizada_at')->nullable();
            $t->foreignId('finalizada_por')->nullable()->constrained('users')->nullOnDelete();
            $this->traza($t);
        });

        Schema::create('actividad_participantes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $t->foreignId('agremiado_id')->constrained('agremiados');
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->unsignedInteger('precision')->nullable();
            $t->timestamp('ultima_senal')->nullable();
            $t->timestamps();
        });

        Schema::create('catalogos', function (Blueprint $t) {
            $t->id();
            $t->string('grupo', 40);
            $t->string('valor', 120);
            $t->unsignedSmallInteger('orden')->default(0);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->unique(['grupo', 'valor']);
        });

        Schema::create('ajustes', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 60)->unique();
            $t->string('valor', 255);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('sys_logs', function (Blueprint $t) {
            $t->id();
            $t->string('modulo', 40)->index();
            $t->string('tabla_afectada', 60)->nullable();
            $t->unsignedBigInteger('registro_id')->nullable();
            $t->string('registro', 120)->nullable();
            $t->string('accion', 80);
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->date('fecha');
            $t->time('hora');
            $t->string('descripcion', 255)->nullable();
            $t->json('cambios_json')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('agente', 255)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['tabla_afectada', 'registro_id']);
            $t->index(['fecha', 'hora']);
        });

        Schema::create('codigos_postales', function (Blueprint $t) {
            $t->id();
            $t->string('cp', 5)->index();
            $t->string('asentamiento', 150);
            $t->string('tipo', 60)->nullable();
            $t->string('municipio', 120);
            $t->string('estado', 60);
            $t->string('ciudad', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['agremiado_id']);
            $t->dropForeign(['rol_id']);
        });
        foreach (['codigos_postales', 'sys_logs', 'ajustes', 'catalogos', 'actividad_participantes', 'actividades', 'curso_inscripciones', 'cursos', 'pedido_detalles', 'pedidos', 'movimientos_inventario', 'archivos', 'productos', 'proveedores', 'apoyos', 'cuotas', 'incidencias', 'agremiados', 'roles'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
