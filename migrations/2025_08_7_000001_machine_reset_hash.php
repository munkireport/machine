<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class MachineResetHash extends Migration
{
    private $tableName = 'machine';

    public function up()
    {
        $capsule = new Capsule();

        # Force reload machine data
        $capsule::unprepared("UPDATE hash SET hash = 'x' WHERE name = '$this->tableName'");
    }
    
    public function down()
    {
        // No going back
    }
}
