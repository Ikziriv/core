<?php
namespace SCart\Core\Front\Models;

use SCart\Core\Front\Models\ShopProduct;
use Illuminate\Database\Eloquent\Model;
use SCart\Core\Front\Models\ShopStore;
use SCart\Core\Front\Models\ModelTrait;


class ShopBrand extends Model
{
    use ModelTrait;

    public $timestamps = false;
    public $table = SC_DB_PREFIX.'shop_brand';
    protected $guarded = [];
    private static $getList = null;
    protected $connection = SC_CONNECTION;


    public static function getListAll()
    {
        if (self::$getList === null) {
            self::$getList = self::get()->keyBy('id');
        }
        return self::$getList;
    }

    public function products()
    {
        return $this->hasMany(ShopProduct::class, 'brand_id', 'id');
    }

    public function stores()
    {
        return $this->belongsToMany(ShopStore::class, ShopBrandStore::class, 'brand_id', 'store_id');
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($brand) {
            $brand->stores()->detach();
        });
    }

    /**
     * [getUrl description]
     * @return [type] [description]
     */
    public function getUrl()
    {
        return sc_route('brand.detail', ['alias' => $this->alias]);
    }

    /*
    Get thumb
    */
    public function getThumb()
    {
        return sc_image_get_path_thumb($this->image);
    }

    /*
    Get image
    */
    public function getImage()
    {
        return sc_image_get_path($this->image);

    }


    public function scopeSort($query, $sortBy = null, $sortOrder = 'asc')
    {
        $sortBy = $sortBy ?? 'sort';
        return $query->orderBy($sortBy, $sortOrder);
    }
    

    /**
     * Get page detail
     *
     * @param   [string]  $key     [$key description]
     * @param   [string]  $type  [id, alias]
     *
     */
    public function getDetail($key, $type = null)
    {
        if(empty($key)) {
            return null;
        }
        $storeId = config('app.storeId');
        $dataSelect = $this->getTable().'.*';
        $data = $this->selectRaw($dataSelect);
        if ($type === null) {
            $data = $data->where($this->getTable().'.id', (int) $key);
        } else {
            $data = $data->where($type, $key);
        }
        if (sc_config_global('MultiStorePro') || sc_config_global('MultiVendorPro')) {
            $tableBrandStore = (new ShopBrandStore)->getTable();
            $tableStore = (new ShopStore)->getTable();
            $data = $data->join($tableBrandStore, $tableBrandStore.'.brand_id', $this->getTable() . '.id');
            $data = $data->join($tableStore, $tableStore . '.id', $tableBrandStore.'.store_id');
            $data = $data->where($tableStore . '.status', '1');
            $data = $data->where($tableBrandStore.'.store_id', $storeId);
        }

        $data = $data->where($this->getTable().'.status', 1);
        return $data->first();
    }


    /**
     * Start new process get data
     *
     * @return  new model
     */
    public function start() {
        return new ShopBrand;
    }

    /**
     * build Query
     */
    public function buildQuery() {
        $storeId = config('app.storeId');
        $dataSelect = $this->getTable().'.*';
        $query = $this->selectRaw($dataSelect)
            ->where($this->getTable().'.status', 1);

        if (sc_config_global('MultiStorePro') || sc_config_global('MultiVendorPro')) {
            $tableBrandStore = (new ShopBrandStore)->getTable();
            $tableStore = (new ShopStore)->getTable();
            $query = $query->join($tableBrandStore, $tableBrandStore.'.brand_id', $this->getTable() . '.id');
            $query = $query->join($tableStore, $tableStore . '.id', $tableBrandStore.'.store_id');
            $query = $query->where($tableStore . '.status', '1');
            $query = $query->where($tableBrandStore.'.store_id', $storeId);
        }

        if (count($this->sc_moreWhere)) {
            foreach ($this->sc_moreWhere as $key => $where) {
                if(count($where)) {
                    $query = $query->where($where[0], $where[1], $where[2]);
                }
            }
        }
        if ($this->sc_random) {
            $query = $query->inRandomOrder();
        } else {
            $ckeckSort = false;
            if (is_array($this->sc_sort) && count($this->sc_sort)) {
                foreach ($this->sc_sort as  $rowSort) {
                    if (is_array($rowSort) && count($rowSort) == 2) {
                        if ($rowSort[0] == 'sort') {
                            $ckeckSort = true;
                        }
                        $query = $query->sort($rowSort[0], $rowSort[1]);
                    }
                }
            }
            //Use field "sort" if haven't above
            if (!$ckeckSort) {
                $query = $query->orderBy($this->getTable().'.sort', 'asc');
            }
            //Default, will sort id
            $query = $query->orderBy($this->getTable().'.id', 'desc');
        }

        return $query;
    }
}
