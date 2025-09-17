<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;

    protected $table = 'data';

    protected $fillable = [
        'tema',
        'topik',
        'topik_uri',
        'indikator',
        'indikator_uri',
        'regional',
        'provinsi',
        'kota',
        'fieldName',
        'dataValue',
        'satuan',
        'sumber',
        'lastupdate',
        'deskripsi',
        'tahun'
    ];

    protected $casts = [
        'tahun' => 'integer',
        'lastupdate' => 'datetime'
    ];
}