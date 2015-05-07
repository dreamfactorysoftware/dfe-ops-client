<?php
namespace DreamFactory\Enterprise\Console\Ops\Providers;

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
    const IOC_NAME = 'dfe.console.client';

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
                $_service = new OpsClientService( $app );

                if ( !\Auth::guest() )
                {
                    $_key = \Auth::user()->getAppKey();

                    return $_service->connect(
                        config( 'dashboard.console-api-url' ),
                        $_key->client_id,
                        $_key->client_secret,
                        config( 'dashboard.console-api-port' )
                    );
                }

                return $_service->connect(
                    config( 'dashboard.console-api-url' ),
                    config( 'dashboard.console-api-client-id' ),
                    config( 'dashboard.console-api-client-secret' ),
                    config( 'dashboard.console-api-port' )
                );
            }
        );
    }

}
