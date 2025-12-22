<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RefPerilakuKerja extends Model 
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_perilaku_kerja';
    
    /**
     * The table primary key field
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Table fillable fields
     *
     * @var array
     */
    protected $fillable = ["kode", "perilaku_kerja", "parent"];
    
    /**
     * Relasi ke parent (self-referencing)
     */
    public function parentPerilakuKerja()
    {
        return $this->belongsTo(RefPerilakuKerja::class, 'parent', 'kode');
    }
    
    /**
     * Relasi ke children
     */
    public function children()
    {
        return $this->hasMany(RefPerilakuKerja::class, 'parent', 'kode');
    }
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}

