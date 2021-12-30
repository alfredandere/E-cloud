<?php

use App\FileEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkspaceIdColumnToWorkspaceableModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       $models = array(FileEntry::class);

       foreach ($models as $model) {
           $table = app($model)->getTable();
           if (!Schema::hasColumn($table, 'workspace_id')) {
               Schema::table($table, function (Blueprint $table) {
                   $table->integer('workspace_id')->unsigned()->nullable()->index();
               });
           }
       }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
