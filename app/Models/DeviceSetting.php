<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A key/value pair kept in the on-device SQLite database. Only small bits of
 * device state live here (the API token, who is signed in); every household
 * record lives on the API.
 *
 * @property string $key
 * @property string|null $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['key', 'value'])]
class DeviceSetting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }
}
