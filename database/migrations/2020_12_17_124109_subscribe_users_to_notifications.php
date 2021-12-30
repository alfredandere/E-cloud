<?php

use App\User;
use Common\Notifications\NotificationSubscription;
use Common\Notifications\SubscribeUserToNotifications;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscribeUsersToNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            foreach (User::whereDoesntHave('notificationSubscriptions')->cursor() as $user) {
                app(SubscribeUserToNotifications::class)->execute($user, null);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
