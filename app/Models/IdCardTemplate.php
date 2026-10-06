namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdCardTemplate extends Model
{
    protected $fillable = [
        'name',
        'front_html',
        'back_html',
        'is_active'
    ];

    public static function active()
    {
        return self::where('is_active', true)->first();
    }
}
