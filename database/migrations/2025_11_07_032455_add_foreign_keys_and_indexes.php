<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->unique(['ci'], 'afiliados_ci_unique');
            $table->unique(['ci'], 'afiliados_uniq_afiliados_ci_unique');
            $table->unique(['codigo'], 'afiliados_uniq_afiliados_codigo_unique');
            $table->index(['zona_id'], 'afiliados_fk_afiliados_zona_id_index');
            $table->foreign("zona_id", "fk_afiliados_zona_id")->references("id")->on("zonas")->onDelete('set null');
        });

        Schema::table('afiliado_requisito', function (Blueprint $table) {
            $table->unique(['afiliado_id', 'requisito_id'], 'afiliado_requisito_idx_afiliado_requisito_unico_unique');
            $table->index(['requisito_id'], 'afiliado_requisito_requisito_id_index');
            $table->foreign("afiliado_id", "afiliado_requisito_ibfk_1")->references("id")->on("afiliados")->onDelete('cascade');
            $table->foreign("requisito_id", "afiliado_requisito_ibfk_2")->references("id")->on("requisitos")->onDelete('cascade');
        });

        Schema::table('cache', function (Blueprint $table) {
            $table->primary(['key']);
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->primary(['key']);
        });

        Schema::table('conexiones', function (Blueprint $table) {
            $table->unique(['codigo_medidor'], 'conexiones_codigo_medidor_unique');
            $table->index(['afiliado_id'], 'conexiones_afiliado_id_foreign_index');
            $table->index(['zona_id'], 'conexiones_fk_conexiones_zona_id_index');
            $table->foreign("afiliado_id", "conexiones_afiliado_id_foreign")->references("id")->on("afiliados")->onDelete('cascade');
            $table->foreign("zona_id", "fk_conexiones_zona_id")->references("id")->on("zonas")->onDelete('set null');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->index(['conexion_id'], 'facturas_conexion_id_foreign_index');
            $table->index(['lectura_id'], 'facturas_lectura_id_foreign_index');
            $table->foreign("conexion_id", "facturas_conexion_id_foreign")->references("id")->on("conexiones")->onDelete('cascade');
            $table->foreign("lectura_id", "facturas_lectura_id_foreign")->references("id")->on("lecturas")->onDelete('cascade');
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->unique(['uuid'], 'failed_jobs_uuid_unique');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['queue'], 'jobs_queue_index');
        });

        Schema::table('lecturas', function (Blueprint $table) {
            $table->unique(['conexion_id', 'periodo'], 'lecturas_uniq_lecturas_conexion_periodo_unique');
            $table->index(['registrado_por'], 'lecturas_registrado_por_foreign_index');
            $table->foreign("conexion_id", "fk_lecturas_conexion")->references("id")->on("conexiones")->onUpdate('cascade');
            $table->foreign("conexion_id", "lecturas_conexion_id_foreign")->references("id")->on("conexiones")->onDelete('cascade');
            $table->foreign("registrado_por", "lecturas_registrado_por_foreign")->references("id")->on("users")->onDelete('set null');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->primary(['permission_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign("permission_id", "model_has_permissions_permission_id_foreign")->references("id")->on("permissions")->onDelete('cascade');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign("role_id", "model_has_roles_role_id_foreign")->references("id")->on("roles")->onDelete('cascade');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index(['factura_id'], 'pagos_factura_id_foreign_index');
            $table->index(['registrado_por'], 'pagos_registrado_por_foreign_index');
            $table->foreign("factura_id", "pagos_factura_id_foreign")->references("id")->on("facturas")->onDelete('cascade');
            $table->foreign("registrado_por", "pagos_registrado_por_foreign")->references("id")->on("users")->onDelete('set null');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->primary(['email']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['name', 'guard_name'], 'permissions_name_guard_name_unique');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unique(['token'], 'personal_access_tokens_token_unique');
            $table->index(['tokenable_type', 'tokenable_id'], 'personal_access_tokens_tokenable_type_tokenable_id_index');
        });

        Schema::table('reclamos', function (Blueprint $table) {
            $table->index(['afiliado_id'], 'reclamos_afiliado_id_index');
            $table->index(['user_id'], 'reclamos_user_id_index');
            $table->index(['reclamo_tipo_id'], 'reclamos_reclamo_tipo_id_index');
            $table->index(['resuelto_por_user_id'], 'reclamos_resuelto_por_user_id_index');
            $table->foreign("afiliado_id", "reclamos_ibfk_1")->references("id")->on("afiliados")->onDelete('cascade');
            $table->foreign("user_id", "reclamos_ibfk_2")->references("id")->on("users")->onDelete('cascade');
            $table->foreign("reclamo_tipo_id", "reclamos_ibfk_3")->references("id")->on("reclamo_tipos")->onDelete('set null');
            $table->foreign("resuelto_por_user_id", "reclamos_ibfk_4")->references("id")->on("users")->onDelete('set null');
        });

        Schema::table('reclamo_tipos', function (Blueprint $table) {
            $table->unique(['nombre'], 'reclamo_tipos_nombre_unique');
        });

        Schema::table('requisitos', function (Blueprint $table) {
            $table->unique(['nombre'], 'requisitos_nombre_unique');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->primary(['permission_id', 'role_id']);
            $table->index(['role_id'], 'role_has_permissions_role_id_foreign_index');
            $table->foreign("permission_id", "role_has_permissions_permission_id_foreign")->references("id")->on("permissions")->onDelete('cascade');
            $table->foreign("role_id", "role_has_permissions_role_id_foreign")->references("id")->on("roles")->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['user_id'], 'sessions_user_id_index');
            $table->index(['last_activity'], 'sessions_last_activity_index');
        });

        Schema::table('tarifa_conceptos', function (Blueprint $table) {
            $table->unique(['tarifa_id', 'codigo'], 'tarifa_conceptos_uq_tc_tarifa_codigo_unique');
            $table->foreign("tarifa_id", "fk_tc_tarifa")->references("id")->on("tarifas")->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email'], 'users_email_unique');
            $table->index(['afiliado_id'], 'users_fk_users_afiliado_index');
            $table->foreign("afiliado_id", "users_afiliado_id_foreign")->references("id")->on("afiliados")->onDelete('set null')->onUpdate('cascade');
        });

        Schema::table('zonas', function (Blueprint $table) {
            $table->unique(['nombre'], 'zonas_nombre_unique');
        });

    }
    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->dropUnique('afiliados_ci_unique');
            $table->dropUnique('uniq_afiliados_ci');
            $table->dropUnique('uniq_afiliados_codigo');
            $table->dropIndex('fk_afiliados_zona_id');
        });

        Schema::table('afiliado_requisito', function (Blueprint $table) {
            $table->dropUnique('idx_afiliado_requisito_unico');
            $table->dropIndex('requisito_id');
        });

        Schema::table('cache', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('conexiones', function (Blueprint $table) {
            $table->dropUnique('conexiones_codigo_medidor_unique');
            $table->dropIndex('conexiones_afiliado_id_foreign');
            $table->dropIndex('fk_conexiones_zona_id');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex('facturas_conexion_id_foreign');
            $table->dropIndex('facturas_lectura_id_foreign');
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropUnique('failed_jobs_uuid_unique');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_index');
        });

        Schema::table('lecturas', function (Blueprint $table) {
            $table->dropUnique('uniq_lecturas_conexion_periodo');
            $table->dropIndex('lecturas_registrado_por_foreign');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('model_has_roles_model_id_model_type_index');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_factura_id_foreign');
            $table->dropIndex('pagos_registrado_por_foreign');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_guard_name_unique');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropUnique('personal_access_tokens_token_unique');
            $table->dropIndex('personal_access_tokens_tokenable_type_tokenable_id_index');
        });

        Schema::table('reclamos', function (Blueprint $table) {
            $table->dropIndex('afiliado_id');
            $table->dropIndex('user_id');
            $table->dropIndex('reclamo_tipo_id');
            $table->dropIndex('resuelto_por_user_id');
        });

        Schema::table('reclamo_tipos', function (Blueprint $table) {
            $table->dropUnique('nombre');
        });

        Schema::table('requisitos', function (Blueprint $table) {
            $table->dropUnique('nombre');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('role_has_permissions_role_id_foreign');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_user_id_index');
            $table->dropIndex('sessions_last_activity_index');
        });

        Schema::table('tarifa_conceptos', function (Blueprint $table) {
            $table->dropUnique('uq_tc_tarifa_codigo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropIndex('fk_users_afiliado');
        });

        Schema::table('zonas', function (Blueprint $table) {
            $table->dropUnique('nombre');
        });

    }
};
