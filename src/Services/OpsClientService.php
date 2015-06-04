<?php
namespace DreamFactory\Enterprise\Console\Ops\Services;

use DreamFactory\Enterprise\Common\Services\BaseService;
use DreamFactory\Enterprise\Common\Traits\VerifiesSignatures;
use DreamFactory\Enterprise\Database\Enums\ProvisionStates;
use DreamFactory\Library\Utility\IfSet;
use DreamFactory\Library\Utility\JsonFile;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpsClientService extends BaseService
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int The API version to target
     */
    const API_VERSION = 1;

    //******************************************************************************
    //* Traits
    //******************************************************************************

    use VerifiesSignatures;

    //*************************************************************************
    //* Members
    //*************************************************************************

    /**
     * @var Client
     */
    protected $_client = null;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param string $url          The url of the app server to use
     * @param string $clientId     Your application's client ID
     * @param string $clientSecret Your application's secret ID
     * @param int    $port         The port on which to connect
     *
     * @return $this
     */
    public function connect( $url, $clientId, $clientSecret, $port = null )
    {
        $_endpoint = trim( $url, '/ ' ) . '/';

        //  Check the endpoint...
        if ( false === parse_url( $_endpoint ) )
        {
            throw new \InvalidArgumentException( 'The specified endpoint "' . $_endpoint . '" is not valid.' );
        }

        //  Check and set credentials
        $this->_setSigningCredentials( $clientId, $clientSecret );

        //  Create our client
        $this->_client = new Client( ['base_url' => $_endpoint] );

        return $this;
    }

    /**
     * @return \stdClass
     */
    public function provisioners()
    {
        return $this->post( 'provisioners' );
    }

    /**
     * @return \stdClass
     */
    public function instances()
    {
        return $this->post( 'instances' );
    }

    /**
     * @param $id
     *
     * @return \stdClass
     */
    public function status( $id )
    {
        $_status = $this->post( 'status', ['id' => $id] );
        $_status->deleted = false;

        if ( !$_status->success )
        {
            if ( isset( $_status->code, $_status->message ) )
            {
                $_status->provisioned = false;
                $_status->deprovisioned = false;
                $_status->trial = false;
                $_status->instanceState = ProvisionStates::DEPROVISIONED;

                if ( !$_status->deleted && Response::HTTP_NOT_FOUND == $_status->code )
                {
                    $_status->deleted = true;
                }
            }
        }

        return $_status;
    }

    /**
     * Register a new user
     *
     * @param array $payload
     *
     * @return bool
     */
    public function register( array $payload )
    {
        if ( empty( $payload ) || null === ( $_email = IfSet::get( $payload, 'email' ) ) )
        {
            $this->error( 'register: empty payload or no email address given.' );

            return false;
        }

        if ( false === ( $_result = $this->post( 'register', $payload ) ) )
        {
            $this->error( 'register: cannot connect to server' );

            return false;
        }

        $_code = 1;

        if ( Response::HTTP_OK != $_code && Response::HTTP_CREATED != $_code )
        {
            $this->error( 'register: error ' . $_code . ' registering new user' );

            return false;
        }

        $this->info( 'register: user creation success ' . print_r( $_result, true ) );

        return true;
    }

    public function provision( array $payload )
    {
        return $this->post( 'provision', $payload );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return array|bool
     */
    public function get( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_GET );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return \stdClass|array|bool
     */
    public function post( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_POST );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return \stdClass|array|bool
     */
    public function delete( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_DELETE );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return \stdClass|array|bool
     */
    public function put( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_PUT );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     * @param string $method
     *
     * @return array|bool|\stdClass
     */
    public function any( $uri, $payload = [], $options = [], $method = Request::METHOD_POST )
    {
        return $this->_apiCall( $uri, $payload, $options, $method );
    }

    /**
     * @param string $url
     * @param array  $payload
     * @param array  $options
     * @param string $method
     * @param bool   $object If true, the result is returned as an object instead of an array
     *
     * @return \stdClass|array|bool
     */
    protected function _apiCall( $url, $payload = [], $options = [], $method = Request::METHOD_POST, $object = true )
    {
        try
        {
            $_request = $this->_client->createRequest(
                $method,
                $url,
                array_merge( $options, ['json' => $this->_signRequest( $payload )] )
            );

            $_response = $this->_client->send( $_request );

            return $_response->json( ['object' => $object] );
        }
        catch ( RequestException $_ex )
        {
            if ( $_ex->hasResponse() )
            {
                return JsonFile::encode( $_ex->getResponse() );
            }
        }

        return false;
    }
}
