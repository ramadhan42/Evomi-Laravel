<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurir_tarifs', function (Blueprint $table) {
            if (! Schema::hasColumn('kurir_tarifs', 'kota_asal')) {
                $table->string('kota_asal', 120)->default('Cisauk')->after('kurir_id');
            }
        });

        DB::table('kurir_tarifs')
            ->whereNull('kota_asal')
            ->orWhere('kota_asal', '')
            ->update(['kota_asal' => 'Cisauk']);

        if ($this->indexExists('kurir_tarifs', 'kurir_tarif_unique')) {
            Schema::table('kurir_tarifs', function (Blueprint $table) {
                $table->dropForeign(['kurir_id']);
            });

            Schema::table('kurir_tarifs', function (Blueprint $table) {
                $table->dropUnique('kurir_tarif_unique');
            });
        }

        if (! $this->indexExists('kurir_tarifs', 'kurir_tarif_unique')) {
            Schema::table('kurir_tarifs', function (Blueprint $table) {
                $table->unique(
                    ['kurir_id', 'kota_asal', 'kota_tujuan', 'berat_min_gram', 'berat_max_gram'],
                    'kurir_tarif_unique'
                );
            });
        }

        Schema::table('kurir_tarifs', function (Blueprint $table) {
            if (! $this->foreignKeyExists('kurir_tarifs', 'kurir_tarifs_kurir_id_foreign')) {
                $table->foreign('kurir_id')->references('id')->on('kurirs')->cascadeOnDelete();
            }
        });

        if (! $this->indexExists('kurir_tarifs', 'kurir_tarif_origin_dest_idx')) {
            Schema::table('kurir_tarifs', function (Blueprint $table) {
                $table->index(
                    ['kota_asal', 'kota_tujuan', 'berat_min_gram', 'berat_max_gram'],
                    'kurir_tarif_origin_dest_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('kurir_tarifs', function (Blueprint $table) {
            if ($this->indexExists('kurir_tarifs', 'kurir_tarif_origin_dest_idx')) {
                $table->dropIndex('kurir_tarif_origin_dest_idx');
            }
        });

        if ($this->indexExists('kurir_tarifs', 'kurir_tarif_unique')) {
            Schema::table('kurir_tarifs', function (Blueprint $table) {
                $table->dropForeign(['kurir_id']);
                $table->dropUnique('kurir_tarif_unique');
            });
        }

        Schema::table('kurir_tarifs', function (Blueprint $table) {
            if (! $this->foreignKeyExists('kurir_tarifs', 'kurir_tarifs_kurir_id_foreign')) {
                $table->foreign('kurir_id')->references('id')->on('kurirs')->cascadeOnDelete();
            }

            if (! $this->indexExists('kurir_tarifs', 'kurir_tarif_unique')) {
                $table->unique(
                    ['kurir_id', 'kota_tujuan', 'berat_min_gram', 'berat_max_gram'],
                    'kurir_tarif_unique'
                );
            }

            if (Schema::hasColumn('kurir_tarifs', 'kota_asal')) {
                $table->dropColumn('kota_asal');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);

        return count($rows) > 0;
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $foreignKey, 'FOREIGN KEY']
        );

        return count($rows) > 0;
    }
};
