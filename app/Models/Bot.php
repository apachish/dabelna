<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;

class Bot extends Model
{
    protected $connection = "mongodb"; // نام اتصال در database.php
    protected $table = 'bots';
    protected $fillable = [
        "token",
        "title",
        "chanel_id",
        "chanel_link",
        "telegram_id",
        "is_bot",
        "first_name",
        "username",
        "can_join_groups",
        "can_read_all_group_messages",
        "supports_inline_queries",
        "can_connect_to_business",
        "has_main_web_app",
        "url",
        "has_custom_certificate",
        "pending_update_count",
        "max_connections",
        "ip_address",
        "status",
        "description"
    ];
}
