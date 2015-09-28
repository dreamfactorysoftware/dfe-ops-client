<?php
namespace DreamFactory\Enterprise\Console\Ops\Providers;

use DreamFactory\Enterprise\Common\Providers\BaseServiceProvider;
use DreamFactory\Enterprise\Console\Ops\Services\OpsClientService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Register the hosting service
 */
class OpsClientServiceProvider extends BaseServiceProvider
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string The name of the service in the IoC
     */
    const IOC_NAME = 'dfe.ops-client';

    //********************************************************************************
    //* Methods
    //********************************************************************************

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //  Register the manager
        $this->singleton(static::IOC_NAME,
            function ($app){
                $_clientId = $_clientSecret = null;

                if (!\Auth::guest()) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    /** @type Collection $_keys */
                    $_keys = \Auth::user()->appKeys();

                    /** @noinspection PhpUndefinedMethodInspection */
                    if (empty($_keys) || 0 == $_keys->count()) {
                        throw new \LogicException('dfe-ops-client: No authorization key found for this client.');
                    }

                    $_key = $_keys->first();
                    $_clientId = $_key->client_id;
                    $_clientSecret = $_key->client_secret;
                    //\Log::debug('client key located for user "' . \Auth::user()->id . '": ' . $_clientId);
                }

                $_service = new OpsClientService($app);

                return $_service->connect(rtrim(config('dfe.security.console-api-url'), '/') . '/',
                    [
                        'client-id'     => $_clientId ?: config('dfe.security.console-api-client-id'),
                        'client-secret' => $_clientSecret ?: config('dfe.security.console-api-client-secret'),
                    ],
                    config('dfe.security.guzzle-config', []));
            });
    }
}
