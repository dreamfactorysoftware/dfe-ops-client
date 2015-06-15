<?php
namespace DreamFactory\Enterprise\Console\Ops\Providers;

use DreamFactory\Enterprise\Common\Enums\AppKeyClasses;
use DreamFactory\Enterprise\Common\Providers\BaseServiceProvider;
use DreamFactory\Enterprise\Console\Ops\Services\OpsClientService;

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
    const IOC_NAME = 'dfe-ops-client';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @type string The actual provisioning service
     */
    protected $_serviceClass = 'DreamFactory\\Enterprise\\Console\\Ops\\Services\\OpsClientService';

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //  Register the manager
        $this->singleton(
            static::IOC_NAME,
            function ( $app )
            {
                $_clientId = $_clientSecret = null;
                $_service = new OpsClientService( $app );

                if ( !\Auth::guest() )
                {
                    $_keys = \Auth::user()->getKeys( AppKeyClasses::USER, \Auth::user()->id );

                    if ( empty( $_keys ) || 0 == $_keys->count() )
                    {
                        throw new \LogicException( 'No authorization key found for this user.' );
                    }

                    $_key = $_keys[0];
                    $_clientId = $_key->client_id;
                    $_clientSecret = $_key->client_secret;
                }

                return $_service->connect(
                    config( 'dfe.security.console-api-url' ),
                    $_clientId ?: config( 'dfe.security.console-api-client-id' ),
                    $_clientSecret ?: config( 'dfe.security.console-api-client-secret' ),
                    config( 'dfe.security.console-api-port' )
                );
            }
        );
    }
}
