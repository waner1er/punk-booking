<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date')->nullable()->index();
            $table->time('load_in_at')->nullable();
            $table->time('set_time')->nullable();
            $table->unsignedSmallInteger('set_duration')->nullable()->comment('minutes');

            $table->string('status')->default('prospect')->index();

            $table->string('deal_type')->nullable();
            $table->decimal('fee', 8, 2)->nullable();
            $table->decimal('travel_costs', 8, 2)->nullable();
            $table->boolean('accommodation')->default(false);
            $table->boolean('meals')->default(false);

            $table->date('next_follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
