<?php namespace DreamFactory\Enterprise\Console\Ops\Facades;

use DreamFactory\Enterprise\Console\Ops\Providers\OpsClientServiceProvider;
use DreamFactory\Enterprise\Console\Ops\Services\OpsClientService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static OpsClientService connect($url, $credentials = [], $config = [])
 * @method static array|\stdClass provisioners($options = [], $object = true)
 * @method static array|\stdClass instances($options = [], $object = true)
 * @method static array|\stdClass status($id, $options = [], $object = true)
 * @method static array|\stdClass register(array $payload = [], $options = [], $object = true)
 * @method static array|\stdClass provision(array $payload, $options = [], $object = true)
 * @method static array|\stdClass deprovision(array $payload, $options = [], $object = true)
 *
 * @see \DreamFactory\Enterprise\Console\Ops\Services\OpsClientService
 */
class OpsClient extends Facade
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return OpsClientServiceProvider::IOC_NAME;
    }
}
