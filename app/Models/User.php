<?php
declare(strict_types=1);

final class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';
}
