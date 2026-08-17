<?php
declare(strict_types=1);

final class PublicProfile extends Model
{
    protected string $table = 'public_profiles';
    protected string $primaryKey = 'user_id';
}
