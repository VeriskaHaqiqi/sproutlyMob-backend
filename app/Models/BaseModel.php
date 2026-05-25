<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;


class BaseModel extends Model
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return \Carbon\Carbon::instance($date)
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s');
    }
}