<?php

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_ref', 20)->unique();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Resource::class, 'resource_id')->constrained();
            $table->string('resource_type', 50);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 50)->default('pending');//enum file
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('EGP');
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();// flexible extra data
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['resource_id', 'resource_type', 'starts_at', 'ends_at'], 'bookings_slot_idx');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
