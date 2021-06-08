<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateFooTable extends Migration
{
    public function up(): void
    {
        Schema::create('foos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bar');
        });
    }

    public function down(): void
    {
        Schema::drop('foos');
    }
}
